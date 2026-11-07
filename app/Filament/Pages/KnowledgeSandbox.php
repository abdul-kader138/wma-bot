<?php

namespace App\Filament\Pages;

use App\Models\Service;
use App\Models\WhatsAppAccount;
use App\Services\FaqMatcher;
use App\Services\KnowledgeRetriever;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;

/**
 * Lets staff type a question and see exactly what FaqMatcher and KnowledgeRetriever
 * would do with it — including candidates that scored below the configured threshold
 * — without sending a real WhatsApp message. Runs the actual production matching code
 * (FaqMatcher::explain() / KnowledgeRetriever::explain()), not a reimplementation.
 */
class KnowledgeSandbox extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.knowledge-sandbox';

    public ?array $data = [];

    public ?array $result = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.knowledge_sandbox');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $superAdminName = (string) config('filament-shield.super_admin.name', 'super_admin');

        if ((bool) config('filament-shield.super_admin.enabled', true) && $user->hasRole($superAdminName)) {
            return true;
        }

        return $user->can('page_KnowledgeSandbox');
    }

    public function getTitle(): string
    {
        return __('admin.nav.knowledge_sandbox');
    }

    public function getSubheading(): string
    {
        return 'Runs the real production matching code against a test question — see every candidate, including ones that score below threshold, without sending a real message.';
    }

    public function mount(): void
    {
        $this->form->fill([
            'whatsapp_account_id' => WhatsAppAccount::where('is_default', true)->value('id'),
            'service' => null,
            'question' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Select::make('whatsapp_account_id')
                    ->label(__('admin.whatsapp_account.label'))
                    ->options(fn () => WhatsAppAccount::pluck('name', 'id'))
                    ->live()
                    ->required(),

                Select::make('service')
                    ->label(__('admin.service_request.fields.service'))
                    ->placeholder(__('admin.knowledge_document.fields.all_services'))
                    ->options(fn (Get $get) => collect(Service::toConfig((int) $get('whatsapp_account_id')))
                        ->map(fn ($s) => $s['label'][app()->getLocale()] ?? $s['label']['en'] ?? '')
                        ->all()),

                Textarea::make('question')
                    ->label('Customer question')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function run(FaqMatcher $faqs, KnowledgeRetriever $knowledge): void
    {
        $data = $this->form->getState();

        $accountId = $data['whatsapp_account_id'] ? (int) $data['whatsapp_account_id'] : null;
        $service = $data['service'] ?? null;
        $question = $data['question'];

        $faqResult = $faqs->explain($question, $service, $accountId);

        try {
            $knowledgeResult = $knowledge->explain($question, $service, $accountId);
            $knowledgeError = null;
        } catch (\Throwable $e) {
            $knowledgeResult = ['max_distance' => 0.0, 'candidates' => []];
            $knowledgeError = $e->getMessage();
        }

        $this->result = [
            'faq' => $faqResult,
            'knowledge' => $knowledgeResult,
            'knowledge_error' => $knowledgeError,
        ];
    }
}
