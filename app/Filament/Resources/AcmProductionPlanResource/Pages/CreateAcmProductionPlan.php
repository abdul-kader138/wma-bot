<?php

namespace App\Filament\Resources\AcmProductionPlanResource\Pages;

use App\Filament\Resources\AcmProductionPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcmProductionPlan extends CreateRecord
{
    protected static string $resource = AcmProductionPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'planned';

        return $data;
    }
}
