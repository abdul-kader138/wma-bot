<?php

namespace App\Filament\Resources\CatalogueEntryResource\Pages;

use App\Filament\Resources\CatalogueEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogueEntries extends ListRecords
{
    protected static string $resource = CatalogueEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
