<?php

namespace App\Filament\Resources\MariaQualityEventResource\Pages;

use App\Filament\Resources\MariaQualityEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMariaQualityEvent extends EditRecord
{
    protected static string $resource = MariaQualityEventResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
