<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Pulls plain text out of a staff-uploaded PDF/DOCX/TXT (KnowledgeDocument::source_path)
 * and writes it into `content`, then hands off to IngestKnowledgeDocument — same
 * destination either way, text just arrived via upload instead of being pasted directly.
 */
class ExtractKnowledgeDocumentText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public KnowledgeDocument $document) {}

    public function handle(): void
    {
        $disk = $this->document->source_disk ?: 'local';
        $path = $this->document->source_path;

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            Log::warning('Knowledge document source file missing; skipping extraction', [
                'document_id' => $this->document->id,
                'path' => $path,
            ]);

            return;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $fullPath = Storage::disk($disk)->path($path);

        try {
            $text = match ($extension) {
                'pdf' => $this->extractPdf($fullPath),
                'docx' => $this->extractDocx($fullPath),
                default => Storage::disk($disk)->get($path),
            };
        } catch (\Throwable $e) {
            Log::error('Knowledge document text extraction failed', [
                'document_id' => $this->document->id,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        // withoutEvents: this is a text-extraction write, not a new edit — the
        // model's saved() hook (KnowledgeDocument::booted()) must not re-fire and
        // re-dispatch this same job in a loop. IngestKnowledgeDocument is the
        // intended next step and is dispatched explicitly below instead.
        KnowledgeDocument::withoutEvents(function () use ($text) {
            $this->document->forceFill(['content' => trim($text)])->save();
        });

        IngestKnowledgeDocument::dispatch($this->document->refresh());
    }

    private function extractPdf(string $path): string
    {
        return (new PdfParser)->parseFile($path)->getText();
    }

    private function extractDocx(string $path): string
    {
        $document = WordIOFactory::load($path);
        $text = [];

        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text[] = (string) $element->getText();
                }
            }
        }

        return implode("\n", array_filter($text));
    }
}
