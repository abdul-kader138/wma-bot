<?php

namespace App\Filament\Resources\CatalogueEntryResource\Pages;

use App\Filament\Resources\CatalogueEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCatalogueEntry extends CreateRecord
{
    protected static string $resource = CatalogueEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['price_minor_units'] = isset($data['price']) && $data['price'] !== null
            ? (int) round($data['price'] * 100)
            : null;
        unset($data['price']);

        return $data;
    }
}
