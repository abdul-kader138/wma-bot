<?php

namespace App\Filament\Resources\AgverseOpportunityResource\Pages;

use App\Filament\Resources\AgverseOpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgverseOpportunity extends EditRecord
{
    protected static string $resource = AgverseOpportunityResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AgverseOpportunityResource::prepareData($data);
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
