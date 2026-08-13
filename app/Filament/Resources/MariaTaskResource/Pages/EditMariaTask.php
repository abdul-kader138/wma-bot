<?php

namespace App\Filament\Resources\MariaTaskResource\Pages;

use App\Filament\Resources\MariaTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMariaTask extends EditRecord
{
    protected static string $resource = MariaTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
