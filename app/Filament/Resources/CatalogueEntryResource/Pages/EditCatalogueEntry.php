<?php

namespace App\Filament\Resources\CatalogueEntryResource\Pages;

use App\Filament\Resources\CatalogueEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalogueEntry extends EditRecord
{
    protected static string $resource = CatalogueEntryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['price_minor_units'] = isset($data['price']) && $data['price'] !== null
            ? (int) round($data['price'] * 100)
            : null;
        unset($data['price']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
