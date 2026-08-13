<?php

namespace App\Filament\Resources\MariaContactResource\Pages;

use App\Filament\Resources\MariaContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMariaContacts extends ListRecords
{
    protected static string $resource = MariaContactResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
