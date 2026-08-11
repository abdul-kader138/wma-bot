<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['whatsapp_account_id'] = $data['accounts'][0] ?? null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->accounts->each(
            fn ($account) => Cache::forget("services:all:{$account->id}")
        );
    }
}
