<?php

namespace App\Filament\Resources\PromptVersionResource\Pages;

use App\Filament\Resources\PromptVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromptVersions extends ListRecords
{
    protected static string $resource = PromptVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
