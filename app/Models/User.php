<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'panel_user'])
            || $this->getAllPermissions()->isNotEmpty();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getAvatarUrl();
    }

    public function getAvatarUrl(): string
    {
        if (filled($this->avatar)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar);
        }

        $initial = strtoupper(mb_substr(trim((string) $this->name), 0, 1));
        $initial  = blank($initial) ? 'U' : $initial;

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128"><rect width="128" height="128" rx="64" fill="#1f2937"/><text x="64" y="82" text-anchor="middle" font-family="Arial,sans-serif" font-size="52" font-weight="700" fill="#9ca3af">%s</text></svg>',
            e($initial)
        );

        return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
}
