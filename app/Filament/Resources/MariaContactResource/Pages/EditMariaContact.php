<?php

namespace App\Filament\Resources\MariaContactResource\Pages;

use App\Filament\Resources\MariaContactResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMariaContact extends EditRecord
{
    protected static string $resource = MariaContactResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
