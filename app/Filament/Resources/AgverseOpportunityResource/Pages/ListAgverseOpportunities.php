<?php

namespace App\Filament\Resources\AgverseOpportunityResource\Pages;

use App\Filament\Resources\AgverseOpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgverseOpportunities extends ListRecords
{
    protected static string $resource = AgverseOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
