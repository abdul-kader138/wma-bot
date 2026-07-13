<?php

namespace App\Filament\Auth;

use App\Models\Setting;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\HtmlString;

class EditProfile extends BaseEditProfile
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Grid::make(3)->schema([

                            Section::make(__('admin.profile.sections.picture'))
                                ->description(__('admin.profile.descriptions.picture'))
                                ->schema([
                                    FileUpload::make('avatar')
                                        ->label(__('admin.profile.fields.avatar'))
                                        ->avatar()
                                        ->imageEditor()
                                        ->circleCropper()
                                        ->disk('public')
                                        ->directory('avatars')
                                        ->visibility('public'),
                                ])
                                ->columnSpan(1),

                            Section::make(__('admin.profile.sections.details'))
                                ->description(__('admin.profile.descriptions.details'))
                                ->schema([
                                    $this->getNameFormComponent(),
                                    $this->getEmailFormComponent(),
                                ])
                                ->columnSpan(2),
                        ]),

                        Section::make(__('admin.profile.sections.security'))
                            ->description(__('admin.profile.descriptions.security'))
                            ->schema([
                                Grid::make(2)->schema([
                                    $this->getPasswordFormComponent(),
                                    $this->getPasswordConfirmationFormComponent(),
                                ]),
                            ]),

                        Section::make(__('admin.profile.sections.two_factor'))
                            ->description(__('admin.profile.descriptions.two_factor'))
                            ->schema([
                                Placeholder::make('two_factor_status')
                                    ->label('')
                                    ->content(fn () => match (true) {
                                        ! $this->isTwoFactorEnabledGlobally() => __('admin.profile.two_factor.disabled_globally'),
                                        $this->getUser()->hasEnabledTwoFactorAuthentication() => __('admin.profile.two_factor.enabled'),
                                        default => __('admin.profile.two_factor.disabled'),
                                    }),
                            ]),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data'),
            ),
        ];
    }

    protected function isTwoFactorEnabledGlobally(): bool
    {
        return (bool) Setting::get('two_factor_enabled', true);
    }

    protected function getHeaderActions(): array
    {
        if (! $this->isTwoFactorEnabledGlobally()) {
            return [];
        }

        /** @var User $user */
        $user = $this->getUser();

        $service = app(TwoFactorAuthenticationService::class);

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return [
                Action::make('showRecoveryCodes')
                    ->label(__('admin.profile.two_factor.show_recovery_codes'))
                    ->color('gray')
                    ->icon('heroicon-o-key')
                    ->modalHeading(__('admin.profile.two_factor.recovery_codes_heading'))
                    ->modalDescription(__('admin.profile.two_factor.recovery_codes_description'))
                    ->modalContent(fn () => new HtmlString(
                        '<div class="grid grid-cols-2 gap-2 font-mono text-sm">'
                        .collect($user->two_factor_recovery_codes ?? [])
                            ->map(fn (string $code) => '<div>'.e($code).'</div>')
                            ->implode('')
                        .'</div>'
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('filament-panels::pages/auth/edit-profile.actions.cancel.label')),

                Action::make('regenerateRecoveryCodes')
                    ->label(__('admin.profile.two_factor.regenerate_recovery_codes'))
                    ->color('gray')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function () use ($user, $service) {
                        $user->forceFill([
                            'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
                        ])->save();

                        Notification::make()
                            ->success()
                            ->title(__('admin.profile.two_factor.regenerated_notification'))
                            ->send();
                    }),

                Action::make('disableTwoFactor')
                    ->label(__('admin.profile.two_factor.disable_action'))
                    ->color('danger')
                    ->icon('heroicon-o-shield-exclamation')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.profile.two_factor.disable_confirm_heading'))
                    ->modalDescription(__('admin.profile.two_factor.disable_confirm_body'))
                    ->action(function () use ($user) {
                        $user->forceFill([
                            'two_factor_secret' => null,
                            'two_factor_recovery_codes' => null,
                            'two_factor_confirmed_at' => null,
                        ])->save();

                        Notification::make()
                            ->success()
                            ->title(__('admin.profile.two_factor.disabled_notification'))
                            ->send();
                    }),
            ];
        }

        return [
            Action::make('enableTwoFactor')
                ->label(__('admin.profile.two_factor.enable_action'))
                ->icon('heroicon-o-shield-check')
                ->fillForm(fn () => ['secret' => $service->generateSecretKey()])
                ->form([
                    Hidden::make('secret'),
                    Placeholder::make('qr_code')
                        ->label(__('admin.profile.two_factor.setup_heading'))
                        ->content(fn (Get $get) => new HtmlString(
                            '<div class="flex flex-col items-center gap-3">'
                            .'<img src="'.$service->qrCodeSvg($user, $get('secret')).'" alt="QR code" class="h-48 w-48" />'
                            .'<code class="rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">'.e($get('secret')).'</code>'
                            .'</div>'
                        )),
                    TextInput::make('code')
                        ->label(__('admin.profile.two_factor.code_label'))
                        ->helperText(__('admin.profile.two_factor.code_helper'))
                        ->required()
                        ->autocomplete('one-time-code')
                        ->rule(fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($service, $get) {
                            if (! $service->verify($get('secret'), (string) $value)) {
                                $fail(__('admin.profile.two_factor.invalid_code'));
                            }
                        }),
                ])
                ->modalHeading(__('admin.profile.sections.two_factor'))
                ->modalDescription(__('admin.profile.two_factor.setup_description'))
                ->modalSubmitActionLabel(__('admin.profile.two_factor.confirm_action'))
                ->action(function (array $data) use ($user, $service) {
                    $user->forceFill([
                        'two_factor_secret' => $data['secret'],
                        'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
                        'two_factor_confirmed_at' => now(),
                    ])->save();

                    Notification::make()
                        ->success()
                        ->title(__('admin.profile.two_factor.enabled_notification'))
                        ->send();
                }),
        ];
    }
}
