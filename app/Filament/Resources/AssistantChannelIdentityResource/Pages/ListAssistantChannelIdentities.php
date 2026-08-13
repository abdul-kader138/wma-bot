<?php

namespace App\Filament\Resources\AssistantChannelIdentityResource\Pages;

use App\Filament\Resources\AssistantChannelIdentityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssistantChannelIdentities extends ListRecords
{
    protected static string $resource = AssistantChannelIdentityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
