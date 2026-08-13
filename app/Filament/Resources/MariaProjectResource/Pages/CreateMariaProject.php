<?php

namespace App\Filament\Resources\MariaProjectResource\Pages;

use App\Filament\Resources\MariaProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMariaProject extends CreateRecord
{
    protected static string $resource = MariaProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
