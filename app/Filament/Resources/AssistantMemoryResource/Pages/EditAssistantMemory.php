<?php

namespace App\Filament\Resources\AssistantMemoryResource\Pages;

use App\Filament\Resources\AssistantMemoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssistantMemory extends EditRecord
{
    protected static string $resource = AssistantMemoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
