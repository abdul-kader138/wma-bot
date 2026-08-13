<?php

namespace App\Filament\Resources\AcmProductionPlanResource\Pages;

use App\Filament\Resources\AcmProductionPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcmProductionPlan extends EditRecord
{
    protected static string $resource = AcmProductionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
