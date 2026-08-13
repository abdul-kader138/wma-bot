<?php

namespace App\Filament\Resources\AssistantProfileResource\Pages;

use App\Filament\Resources\AssistantProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssistantProfile extends CreateRecord
{
    protected static string $resource = AssistantProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->isAdmin()) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }
}
