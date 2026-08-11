<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 99;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.groups.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.audit_logs');
    }

    public static function getModelLabel(): string
    {
        return __('admin.audit_log.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.audit_log.label_plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label(__('admin.audit_log.fields.time'))->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('actor.name')->label(__('admin.audit_log.fields.actor'))->searchable()->placeholder('System'),
                Tables\Columns\TextColumn::make('owner.name')->label(__('admin.audit_log.fields.owner'))->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('category')->badge()->sortable(),
                Tables\Columns\TextColumn::make('action')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subject_path')->label(__('admin.audit_log.fields.path'))->searchable()->limit(45)->tooltip(fn (AuditLog $record) => $record->subject_path),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->toggleable(),
                Tables\Columns\TextColumn::make('metadata')->label(__('admin.audit_log.fields.details'))
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '—')
                    ->limit(60)->wrap()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('category')->options(['documents' => 'Documents']),
                SelectFilter::make('action')->options(fn () => AuditLog::query()->distinct()->orderBy('action')->pluck('action', 'action')),
                SelectFilter::make('actor_id')->label(__('admin.audit_log.fields.actor'))->options(fn () => User::orderBy('name')->pluck('name', 'id'))->searchable(),
                SelectFilter::make('owner_id')->label(__('admin.audit_log.fields.owner'))->options(fn () => User::orderBy('name')->pluck('name', 'id'))->searchable(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAuditLogs::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
