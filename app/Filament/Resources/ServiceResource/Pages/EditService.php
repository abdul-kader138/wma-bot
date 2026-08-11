<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    private array $previousAccountIds = [];

    protected function beforeSave(): void
    {
        $this->previousAccountIds = $this->record->accounts()->pluck('whatsapp_accounts.id')->all();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['whatsapp_account_id'] = $data['accounts'][0] ?? null;

        return $data;
    }

    protected function afterSave(): void
    {
        collect($this->previousAccountIds)
            ->merge($this->record->accounts()->pluck('whatsapp_accounts.id'))
            ->unique()
            ->each(fn ($accountId) => Cache::forget("services:all:{$accountId}"));
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
