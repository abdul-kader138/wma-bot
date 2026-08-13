<?php

namespace App\Filament\Resources\MariaTaskResource\Pages;

use App\Filament\Resources\MariaTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMariaTask extends CreateRecord
{
    protected static string $resource = MariaTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
