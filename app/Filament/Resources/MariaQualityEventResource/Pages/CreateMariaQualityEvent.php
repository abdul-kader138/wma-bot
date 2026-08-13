<?php

namespace App\Filament\Resources\MariaQualityEventResource\Pages;

use App\Filament\Resources\MariaQualityEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMariaQualityEvent extends CreateRecord
{
    protected static string $resource = MariaQualityEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['reported_by'] = auth()->id();

        return $data;
    }
}
