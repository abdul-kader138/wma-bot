<?php

namespace App\Filament\Resources\MariaProjectResource\Pages;

use App\Filament\Resources\MariaProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMariaProjects extends ListRecords
{
    protected static string $resource = MariaProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
