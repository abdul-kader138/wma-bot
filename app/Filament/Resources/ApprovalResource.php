<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovalResource\Pages;
use App\Models\Approval;
use App\Services\Maria\ApprovalService;
use App\Services\Maria\ApprovedGoogleActionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApprovalResource extends MariaResource
{
    protected static ?string $model = Approval::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Maria Assistant';

    protected static ?int $navigationSort = 12;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isAdmin() ? $query : $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('action_type')->disabled(),
            Forms\Components\Textarea::make('proposed_action')->disabled()->columnSpanFull(),
            Forms\Components\TextInput::make('recipient_channel')->disabled(),
            Forms\Components\TextInput::make('risk_level')->disabled(),
            Forms\Components\Textarea::make('preview')->disabled()->rows(10)->columnSpanFull(),
            Forms\Components\KeyValue::make('attachments')->disabled()->columnSpanFull(),
            Forms\Components\TextInput::make('decision')->disabled(),
            Forms\Components\DateTimePicker::make('expires_at')->displayFormat(config('app.display_datetime_format', 'd/m/Y H:i'))->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('proposed_action')->limit(70)->searchable(),
            Tables\Columns\TextColumn::make('recipient_channel')->placeholder('Internal'),
            Tables\Columns\TextColumn::make('risk_level')->badge(),
            Tables\Columns\TextColumn::make('decision')->badge(),
            Tables\Columns\TextColumn::make('expires_at')->dateTime(config('app.display_datetime_format', 'd/m/Y H:i'))->sortable(),
        ])->defaultSort('created_at', 'desc')->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\Action::make('approve')->color('success')->icon('heroicon-o-check')->requiresConfirmation()
                ->visible(fn (Approval $record) => $record->isPendingAndCurrent())
                ->action(function (Approval $record): void {
                    app(ApprovalService::class)->approve($record, auth()->user(), $record->proposed_content);
                    Notification::make()->title('Action approved')->success()->send();
                }),
            Tables\Actions\Action::make('reject')->color('danger')->icon('heroicon-o-x-mark')->requiresConfirmation()
                ->visible(fn (Approval $record) => $record->isPendingAndCurrent())
                ->action(fn (Approval $record) => app(ApprovalService::class)->decide($record, auth()->user(), 'rejected')),
            Tables\Actions\Action::make('execute_google_action')->label('Execute approved action')->color('warning')->icon('heroicon-o-paper-airplane')
                ->visible(fn (Approval $record) => $record->decision === 'approved' && in_array($record->action_type, ['google_email_send', 'google_calendar_create'], true) && ! $record->actions()->where('status', 'completed')->exists())
                ->requiresConfirmation()->modalDescription('This performs the exact approved external action. It cannot be automatically undone.')
                ->action(function (Approval $record): void {
                    app(ApprovedGoogleActionService::class)->execute($record, auth()->user());
                    Notification::make()->title('Approved Google action completed')->success()->send();
                }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListApprovals::route('/'), 'view' => Pages\ViewApproval::route('/{record}')];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('decision', 'pending')->where('expires_at', '>', now())->count();

        return $count ? (string) $count : null;
    }
}
