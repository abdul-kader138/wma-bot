<?php

namespace App\Filament\Resources\PromptVersionResource\Pages;

use App\Filament\Resources\PromptVersionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePromptVersion extends CreateRecord
{
    protected static string $resource = PromptVersionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return PromptVersionResource::prepareData($data);
    }
}
