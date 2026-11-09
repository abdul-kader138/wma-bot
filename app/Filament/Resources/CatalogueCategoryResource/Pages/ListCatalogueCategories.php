<?php

namespace App\Filament\Resources\CatalogueCategoryResource\Pages;

use App\Filament\Resources\CatalogueCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogueCategories extends ListRecords
{
    protected static string $resource = CatalogueCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
