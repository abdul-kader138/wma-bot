<?php

namespace App\Filament\Resources;

use App\Services\Maria\MariaAccess;
use Filament\Resources\Resource;

abstract class MariaResource extends Resource
{
    public static function canAccess(): bool
    {
        return MariaAccess::allowed(auth()->user()) && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return MariaAccess::allowed(auth()->user()) && parent::shouldRegisterNavigation();
    }
}
