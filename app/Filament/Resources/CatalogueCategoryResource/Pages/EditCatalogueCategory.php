<?php

namespace App\Filament\Resources\CatalogueCategoryResource\Pages;

use App\Filament\Resources\CatalogueCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalogueCategory extends EditRecord
{
    protected static string $resource = CatalogueCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
