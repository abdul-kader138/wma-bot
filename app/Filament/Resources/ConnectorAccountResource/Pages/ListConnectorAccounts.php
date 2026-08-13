<?php

namespace App\Filament\Resources\ConnectorAccountResource\Pages;

use App\Filament\Resources\ConnectorAccountResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListConnectorAccounts extends ListRecords
{
    protected static string $resource = ConnectorAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect_google')->label('Connect Google Workspace')->icon('heroicon-o-link')
                ->url(route('panel-api.connectors.google.redirect')),
        ];
    }
}
