<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorAuthenticationService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret,
        );
    }

    public function verify(string $secret, string $code): bool
    {
        $code = trim($code);

        if ($code === '') {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $code, 1) !== false;
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::random(10).'-'.Str::random(10))
            ->all();
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        return in_array($code, $codes, true);
    }

    public function consumeRecoveryCode(User $user, string $code): void
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        $user->forceFill([
            'two_factor_recovery_codes' => array_values(array_diff($codes, [$code])),
        ])->save();
    }
}
