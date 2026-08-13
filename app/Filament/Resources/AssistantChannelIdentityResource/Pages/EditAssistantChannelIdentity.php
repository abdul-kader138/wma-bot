<?php

namespace App\Filament\Resources\AssistantChannelIdentityResource\Pages;

use App\Filament\Resources\AssistantChannelIdentityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssistantChannelIdentity extends EditRecord
{
    protected static string $resource = AssistantChannelIdentityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
