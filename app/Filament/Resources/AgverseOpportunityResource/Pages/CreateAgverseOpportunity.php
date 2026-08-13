<?php

namespace App\Filament\Resources\AgverseOpportunityResource\Pages;

use App\Filament\Resources\AgverseOpportunityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgverseOpportunity extends CreateRecord
{
    protected static string $resource = AgverseOpportunityResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return AgverseOpportunityResource::prepareData($data);
    }
}
