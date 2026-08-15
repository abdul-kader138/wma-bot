<?php

namespace App\Filament\Resources;

use App\Services\Maria\MariaAccess;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

abstract class MariaResource extends Resource
{
    /**
     * Whether non-admin users only see their own records (the `user_id` column).
     * Set to false in resources whose visibility is already fully governed by their
     * Shield permission instead of per-record ownership (e.g. shared reference data).
     */
    protected static bool $scopeToOwner = true;

    public static function canAccess(): bool
    {
        return MariaAccess::allowed(auth()->user()) && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return MariaAccess::allowed(auth()->user()) && parent::shouldRegisterNavigation();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! static::$scopeToOwner || auth()->user()?->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', auth()->id());
    }
}
