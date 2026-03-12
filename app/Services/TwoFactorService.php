<?php

namespace App\Services;

use App\DTOs\ExceptionParametersDTO;
use App\Models\RecoveryCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorService
{
    private const MAX_ATTEMPTS    = 3;
    private const LOCKOUT_MINUTES = 5;

    public function __construct(
        private readonly Google2FA $google2fa,
        private readonly ThrowJsonExceptionService $throwJsonExceptionService
    ) {
    }

    public function generateQR(User $user): array
    {
        Log::info('Generating 2FA setup', ['user_id' => $user->id]);

        $secret = $this->google2fa->generateSecretKey();
        $qrUrl  = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->username,
            $secret
        );

        $svg = (new Writer(
            new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd())
        ))->writeString($qrUrl);

        Log::info('2FA setup generated', ['user_id' => $user->id]);

        return [
            'qr_code' => 'data:image/svg+xml;base64,' . base64_encode($svg),
            'secret'  => $secret,
        ];
    }

    public function updateSecret(User $user, string $secret): void
    {
        $user->update(['two_factor_secret' => $secret]);
    }

    public function createRecoveryCodes(User $user): array
    {
        if ($user->recoveryCodes()->whereNull('used_at')->exists()) {
            return [];
        }

        $user->recoveryCodes()->delete();

        $codes = Collection::times(8, fn() => strtoupper(Str::random(10)))->all();

        $user->recoveryCodes()->createMany(
            array_map(fn(string $code) => ['code' => Hash::make($code)], $codes)
        );

        Log::info('Recovery codes generated', ['user_id' => $user->id]);

        return $codes;
    }

    public function useRecoveryCode(User $user, string $code): void
    {
        $recovery = $user->recoveryCodes()
            ->whereNull('used_at')
            ->get()
            ->first(fn($r) => Hash::check($code, $r->code));

        if (! $recovery) {
            Log::warning('2FA recovery failed: invalid or used code', ['user_id' => $user->id]);

            $this->throwJsonExceptionService->throwJsonException(
                new ExceptionParametersDTO(
                    message: 'Invalid or already used recovery code.',
                    status: Response::HTTP_UNAUTHORIZED,
                    global_error: false,
					is_show_modal: true,
					is_custom_message: true,
                )
            );
        }

        $recovery->update(['used_at' => now()]);
        $user->update(['two_factor_enabled' => false]);

        Log::info('2FA disabled via recovery code', ['user_id' => $user->id]);
    }

    public function enable(User $user, string $otp): void
    {
        Log::info('Enabling 2FA', ['user_id' => $user->id]);

        $this->verifyOtp($user, $otp);

        $user->update(['two_factor_enabled' => true]);

        Log::info('2FA enabled', ['user_id' => $user->id]);
    }

    public function disable(User $user, string $otp): void
    {
        Log::info('Disabling 2FA', ['user_id' => $user->id]);

        $this->verifyOtp($user, $otp);

        $user->recoveryCodes()->delete();

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret'  => null,
        ]);

        Log::info('2FA disabled', ['user_id' => $user->id]);
    }

    public function verify(User $user, string $otp): void
    {
        Log::info('Verifying 2FA OTP', ['user_id' => $user->id]);

        $this->ensureNotLockedOut($user);
        $this->verifyOtp($user, $otp);
        $this->resetTwoFactorAttempts($user);
        $this->activateIfDisabled($user);

        Log::info('2FA OTP verified', ['user_id' => $user->id]);
    }

    private function activateIfDisabled(User $user): void
    {
        if ($user->two_factor_enabled) {
            return;
        }

        $user->update(['two_factor_enabled' => true]);
        Log::info('2FA auto-enabled on first verify', ['user_id' => $user->id]);
    }

    private function ensureNotLockedOut(User $user): void
    {
        if ($user->two_factor_attempts < self::MAX_ATTEMPTS || ! $user->two_factor_attempts_expiry) {
            return;
        }

        if (now()->gte($user->two_factor_attempts_expiry)) {
            $this->resetTwoFactorAttempts($user);
            return;
        }

        $minutesLeft = (int) ceil(now()->diffInMinutes($user->two_factor_attempts_expiry, false));

        Log::warning('2FA locked', ['user_id' => $user->id]);

        $this->throwJsonExceptionService->throwJsonException(
            new ExceptionParametersDTO(
                message: "Too many attempts. Try again in {$minutesLeft} minute(s).",
                status: Response::HTTP_TOO_MANY_REQUESTS,
                global_error: true,
                is_show_modal: false,
                is_custom_message: true,
            )
        );
    }

    private function verifyOtp(User $user, string $otp): void
    {
        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $otp);

        if (! $valid) {
            $user->increment('two_factor_attempts');
            $user->two_factor_attempts_expiry = now()->addMinutes(self::LOCKOUT_MINUTES);
            $user->save();

            $remaining = self::MAX_ATTEMPTS - $user->two_factor_attempts;
            $message   = $remaining > 0
                ? "Invalid 2FA code. {$remaining} attempt(s) remaining."
                : "Too many attempts. Try again in " . self::LOCKOUT_MINUTES . " minutes.";

            Log::warning('2FA failed: invalid OTP', ['user_id' => $user->id, 'attempts' => $user->two_factor_attempts]);

            $this->throwJsonExceptionService->throwJsonException(
                new ExceptionParametersDTO(
                    message: $message,
                    status: Response::HTTP_UNAUTHORIZED,
                    global_error: false,
                    is_show_modal: false,
                    is_custom_message: false,
                )
            );
        }
    }

    private function resetTwoFactorAttempts(User $user): void
    {
        $user->two_factor_attempts        = 0;
        $user->two_factor_attempts_expiry = null;
        $user->save();
    }
}
