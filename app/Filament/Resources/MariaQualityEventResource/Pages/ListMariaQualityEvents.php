<?php

namespace App\Filament\Resources\MariaQualityEventResource\Pages;

use App\Filament\Resources\MariaQualityEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMariaQualityEvents extends ListRecords
{
    protected static string $resource = MariaQualityEventResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
