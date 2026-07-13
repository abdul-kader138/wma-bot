<?php

namespace App\Filament\Auth;

use App\Models\Setting;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public bool $twoFactorChallenge = false;

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        return $this->twoFactorChallenge
            ? $this->authenticateTwoFactorChallenge()
            : $this->authenticateCredentials();
    }

    protected function authenticateCredentials(): ?LoginResponse
    {
        $data = $this->form->getState();

        $credentials = $this->getCredentialsFromFormData($data);

        if (! Filament::auth()->validate($credentials)) {
            $this->throwFailureValidationException();
        }

        /** @var User|null $user */
        $user = Filament::auth()->getProvider()->retrieveByCredentials($credentials);

        if (($user instanceof FilamentUser) && (! $user->canAccessPanel(Filament::getCurrentPanel()))) {
            $this->throwFailureValidationException();
        }

        if ($this->isTwoFactorEnabledGlobally() && $user->hasEnabledTwoFactorAuthentication()) {
            session([
                'two_factor_authentication_user_id' => $user->getKey(),
                'two_factor_authentication_remember' => (bool) ($data['remember'] ?? false),
            ]);

            $this->startTwoFactorChallenge();

            return null;
        }

        Filament::auth()->login($user, (bool) ($data['remember'] ?? false));

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function authenticateTwoFactorChallenge(): ?LoginResponse
    {
        $userId = session('two_factor_authentication_user_id');

        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            $this->resetTwoFactorChallenge();
            $this->throwFailureValidationException();
        }

        if (! $this->isTwoFactorEnabledGlobally()) {
            Filament::auth()->login($user, (bool) session('two_factor_authentication_remember', false));

            $this->resetTwoFactorChallenge();

            session()->regenerate();

            return app(LoginResponse::class);
        }

        $data = $this->form->getState();
        $code = trim((string) ($data['code'] ?? ''));

        $service = app(TwoFactorAuthenticationService::class);

        $isValidTotp = filled($code) && $service->verify($user->two_factor_secret, $code);
        $isValidRecoveryCode = (! $isValidTotp) && filled($code) && $service->verifyRecoveryCode($user, $code);

        if (! ($isValidTotp || $isValidRecoveryCode)) {
            throw ValidationException::withMessages([
                'data.code' => __('The provided two-factor code was invalid.'),
            ]);
        }

        if ($isValidRecoveryCode) {
            $service->consumeRecoveryCode($user, $code);
        }

        Filament::auth()->login($user, (bool) session('two_factor_authentication_remember', false));

        $this->resetTwoFactorChallenge();

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function isTwoFactorEnabledGlobally(): bool
    {
        return (bool) Setting::get('two_factor_enabled', true);
    }

    protected function startTwoFactorChallenge(): void
    {
        $this->twoFactorChallenge = true;
        $this->hasCachedForms = false;

        $this->form->fill(['code' => '']);
    }

    protected function resetTwoFactorChallenge(): void
    {
        session()->forget(['two_factor_authentication_user_id', 'two_factor_authentication_remember']);

        $this->twoFactorChallenge = false;
        $this->hasCachedForms = false;
    }

    protected function getForms(): array
    {
        if ($this->twoFactorChallenge) {
            return [
                'form' => $this->form(
                    $this->makeForm()
                        ->schema([$this->getTwoFactorCodeFormComponent()])
                        ->statePath('data'),
                ),
            ];
        }

        return parent::getForms();
    }

    protected function getTwoFactorCodeFormComponent(): Component
    {
        return TextInput::make('code')
            ->label(__('admin.auth.two_factor.code_label'))
            ->helperText(__('admin.auth.two_factor.code_helper'))
            ->autofocus()
            ->required()
            ->autocomplete('one-time-code')
            ->extraInputAttributes(['inputmode' => 'numeric']);
    }

    protected function getFormActions(): array
    {
        if ($this->twoFactorChallenge) {
            return [
                $this->getAuthenticateFormAction(),
                Action::make('cancelTwoFactorChallenge')
                    ->label(__('admin.auth.two_factor.back_to_login'))
                    ->link()
                    ->action(function () {
                        $this->resetTwoFactorChallenge();
                        $this->form->fill();
                    }),
            ];
        }

        return parent::getFormActions();
    }

    public function getHeading(): string|Htmlable
    {
        return $this->twoFactorChallenge
            ? __('admin.auth.two_factor.heading')
            : parent::getHeading();
    }
}
