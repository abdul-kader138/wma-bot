<?php

namespace App\Filament\Resources\AssistantMemoryResource\Pages;

use App\Filament\Resources\AssistantMemoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssistantMemory extends CreateRecord
{
    protected static string $resource = AssistantMemoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['source'] = 'manual';

        return $data;
    }
}
