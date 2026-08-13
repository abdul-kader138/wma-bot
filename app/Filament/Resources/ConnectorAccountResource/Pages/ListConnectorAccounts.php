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
            Action::make('enable_google_writes')->label('Enable approved writes')->icon('heroicon-o-pencil-square')
                ->color('warning')->requiresConfirmation()
                ->modalDescription('Google will request Gmail send and Calendar event permissions. Maria still requires a separate exact-content approval before every write.')
                ->url(route('panel-api.connectors.google.write-redirect')),
        ];
    }
}
