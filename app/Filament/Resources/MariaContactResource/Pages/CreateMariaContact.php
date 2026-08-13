<?php

namespace App\Filament\Resources\MariaContactResource\Pages;

use App\Filament\Resources\MariaContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMariaContact extends CreateRecord
{
    protected static string $resource = MariaContactResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
