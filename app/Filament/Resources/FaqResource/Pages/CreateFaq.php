<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['whatsapp_account_id'] = $data['accounts'][0] ?? null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->accounts->each(
            fn ($account) => Cache::forget("faqs:active:{$account->id}")
        );
    }
}
