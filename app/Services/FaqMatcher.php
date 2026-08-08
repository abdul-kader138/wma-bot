<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\Setting;
use Illuminate\Support\Collection;

class FaqMatcher
{
    private const FUZZY_THRESHOLD = 0.5;

    /** Below this length, skip the "needle contains input" reverse check to avoid short/generic messages over-matching. */
    private const MIN_LENGTH_FOR_REVERSE_CONTAINS = 8;

    public function match(string $text, ?string $service): ?Faq
    {
        $normalized = $this->normalize($text);
        if ($normalized === '') {
            return null;
        }

        $candidates = $this->candidates($service);

        foreach ($candidates as $faq) {
            foreach ($this->triggerTexts($faq) as $trigger) {
                $needle = $this->normalize($trigger);
                if ($needle === '') {
                    continue;
                }

                if (str_contains($normalized, $needle)) {
                    return $faq;
                }

                if (mb_strlen($normalized) >= self::MIN_LENGTH_FOR_REVERSE_CONTAINS
                    && str_contains($needle, $normalized)) {
                    return $faq;
                }
            }
        }

        $words     = $this->words($normalized);
        $best      = null;
        $bestScore = 0.0;

        foreach ($candidates as $faq) {
            $score = $this->overlapScore($words, $faq);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $faq;
            }
        }

        $threshold = (float) (Setting::get('faq_confidence_threshold') ?? self::FUZZY_THRESHOLD);

        return $bestScore >= $threshold ? $best : null;
    }

    /** Question text (all languages) plus trigger phrases — anything the user might literally type. */
    private function triggerTexts(Faq $faq): array
    {
        return array_merge($faq->questionVariants(), $faq->keywords ?? []);
    }

    protected function candidates(?string $service): Collection
    {
        return Faq::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('service')->orWhere('service', $service))
            ->get();
    }

    private function overlapScore(array $words, Faq $faq): float
    {
        $targetWords = [];
        foreach ($this->triggerTexts($faq) as $text) {
            $targetWords = array_merge($targetWords, $this->words($this->normalize($text)));
        }
        $targetWords = array_unique($targetWords);

        if (empty($words) || empty($targetWords)) {
            return 0.0;
        }

        $intersection = array_intersect($words, $targetWords);

        // Overlap coefficient (intersection / smaller set), not Jaccard (intersection / union):
        // a short user message shouldn't be penalized just because the FAQ's combined
        // question + trigger phrases are much longer.
        return count($intersection) / min(count($words), count($targetWords));
    }

    /** Words of 3+ characters, to keep short stop words from diluting the overlap score. */
    private function words(string $normalized): array
    {
        return array_values(array_filter(explode(' ', $normalized), fn ($w) => mb_strlen($w) > 2));
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
