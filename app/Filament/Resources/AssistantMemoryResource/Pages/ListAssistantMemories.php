<?php

namespace App\Filament\Resources\AssistantMemoryResource\Pages;

use App\Filament\Resources\AssistantMemoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssistantMemories extends ListRecords
{
    protected static string $resource = AssistantMemoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
