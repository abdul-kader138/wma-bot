<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DocumentManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.document-manager';

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.documents');
    }

    public function getTitle(): string
    {
        return __('admin.nav.documents');
    }
}
