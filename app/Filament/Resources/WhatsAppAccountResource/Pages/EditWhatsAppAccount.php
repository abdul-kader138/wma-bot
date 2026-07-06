<?php

namespace App\Filament\Resources\WhatsAppAccountResource\Pages;

use App\Filament\Resources\WhatsAppAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppAccount extends EditRecord
{
    protected static string $resource = WhatsAppAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
