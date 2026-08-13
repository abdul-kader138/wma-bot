<?php

namespace App\Filament\Resources\MariaTaskResource\Pages;

use App\Filament\Resources\MariaTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMariaTasks extends ListRecords
{
    protected static string $resource = MariaTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
