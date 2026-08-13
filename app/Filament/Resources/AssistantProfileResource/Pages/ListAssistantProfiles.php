<?php

namespace App\Filament\Resources\AssistantProfileResource\Pages;

use App\Filament\Resources\AssistantProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssistantProfiles extends ListRecords
{
    protected static string $resource = AssistantProfileResource::class;

    protected function getHeaderActions(): array
    {
        return AssistantProfileResource::canCreate() ? [Actions\CreateAction::make()] : [];
    }
}
