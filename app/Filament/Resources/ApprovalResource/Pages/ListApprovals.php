<?php

namespace App\Filament\Resources\ApprovalResource\Pages;

use App\Filament\Resources\ApprovalResource;
use App\Models\ConnectorAccount;
use App\Services\Maria\ApprovedGoogleActionService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListApprovals extends ListRecords
{
    protected static string $resource = ApprovalResource::class;

    protected function getHeaderActions(): array
    {
        $connectorOptions = fn () => ConnectorAccount::query()->where('user_id', auth()->id())->where('provider', 'google')->where('status', 'active')->pluck('email', 'id')->all();

        return [
            Action::make('request_email_send')->label('Prepare email send')->icon('heroicon-o-envelope')->form([
                Forms\Components\Select::make('connector_account_id')->label('Google account')->options($connectorOptions)->required(),
                Forms\Components\TextInput::make('to')->email()->required(),
                Forms\Components\TextInput::make('cc'), Forms\Components\TextInput::make('bcc'),
                Forms\Components\TextInput::make('subject')->required()->columnSpanFull(),
                Forms\Components\Textarea::make('body')->required()->rows(12)->columnSpanFull(),
                Forms\Components\TextInput::make('thread_id')->label('Gmail thread ID')->columnSpanFull(),
            ])->action(function (array $data): void {
                app(ApprovedGoogleActionService::class)->requestEmail(auth()->user(), $data);
                Notification::make()->title('Email send added to approval queue')->success()->send();
            }),
            Action::make('request_calendar_event')->label('Prepare calendar event')->icon('heroicon-o-calendar-days')->form([
                Forms\Components\Select::make('connector_account_id')->label('Google account')->options($connectorOptions)->required(),
                Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
                Forms\Components\Textarea::make('description')->columnSpanFull(),
                Forms\Components\TextInput::make('location')->columnSpanFull(),
                Forms\Components\DateTimePicker::make('starts_at')->displayFormat(config('app.display_datetime_format', 'd/m/Y H:i'))->required(), Forms\Components\DateTimePicker::make('ends_at')->displayFormat(config('app.display_datetime_format', 'd/m/Y H:i'))->required()->after('starts_at'),
                Forms\Components\Select::make('timezone')->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->searchable()->default(auth()->user()->assistantProfile?->timezone ?? config('app.timezone'))->required(),
                Forms\Components\TagsInput::make('attendees')->placeholder('person@example.com')->columnSpanFull(),
            ])->action(function (array $data): void {
                app(ApprovedGoogleActionService::class)->requestCalendarEvent(auth()->user(), $data);
                Notification::make()->title('Calendar event added to approval queue')->success()->send();
            }),
        ];
    }
}
