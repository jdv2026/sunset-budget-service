<?php

namespace App\Services;

use App\DTOs\ExceptionParametersDTO;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    private const MAX_ATTEMPTS   = 3;
    private const LOCKOUT_MINUTES = 5;

    public function __construct(
        private readonly ThrowJsonExceptionService $throwJsonExceptionService
    ) {
    }

    public function login(string $username, string $password): void
    {
        $user = $this->findUser($username);

        $this->ensureNotLockedOut($user);
        $this->validatePassword($user, $password);
        $this->resetAttempts($user);
    }

    public function generateToken(User $user): string
    {
        return $this->generateEncryptedToken($user);
    }

    public function generatePreAuthToken(User $user): string
    {
        $token = JWTAuth::claims([
            'jti'      => Str::uuid()->toString(),
            'type'     => $user->type,
            'pre_auth' => true,
            'exp'      => now()->addMinutes(5)->timestamp,
        ])->fromUser($user);

        return openssl_encrypt(
            $token,
            'aes-256-cbc',
            base64_decode(config('app.AES_KEY')),
            0,
            hex2bin(config('app.AES_IV')),
        );
    }

    public function resolvePreAuthUser(string $encryptedToken): User
    {
        $decrypted = openssl_decrypt(
            $encryptedToken,
            'aes-256-cbc',
            base64_decode(config('app.AES_KEY')),
            0,
            hex2bin(config('app.AES_IV'))
        );

        if (! $decrypted) {
            $this->throwNotFoundException('Invalid token.', Response::HTTP_UNAUTHORIZED, false, false, false);
        }

        try {
            $payload = JWTAuth::setToken($decrypted)->getPayload();
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            $this->throwNotFoundException('Invalid token.', Response::HTTP_UNAUTHORIZED, false, false, false);
        }

        if (! $payload->get('pre_auth')) {
            $this->throwNotFoundException('Invalid token.', Response::HTTP_UNAUTHORIZED, false, false, false);
        }

        return JWTAuth::setToken($decrypted)->authenticate();
    }

    public function handleLogout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function findUser(string $username): User
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            Log::warning('Login failed: user not found', ['username' => $username]);
            $this->throwNotFoundException('Invalid credentials.', Response::HTTP_NOT_FOUND, false, false, false);
        }

        return $user;
    }

    private function ensureNotLockedOut(User $user): void
    {
        if ($user->attempts < self::MAX_ATTEMPTS || ! $user->attempts_expiry) {
            return;
        }

        if (now()->gte($user->attempts_expiry)) {
            $this->resetAttempts($user);
            return;
        }

        $minutesLeft = (int) ceil(now()->diffInMinutes($user->attempts_expiry, false));

        Log::warning('Login failed: account locked', ['username' => $user->username]);

		$this->throwNotFoundException("Too many attempts. Try again in {$minutesLeft} minute(s).", Response::HTTP_TOO_MANY_REQUESTS, true, false, true);
    }

    private function validatePassword(User $user, string $password): void
    {
        if (Hash::check($password, $user->password)) {
            return;
        }

        $user->increment('attempts');
        $user->attempts_expiry = now()->addMinutes(self::LOCKOUT_MINUTES);
        $user->save();

        Log::warning('Login failed: invalid password', ['username' => $user->username, 'attempts' => $user->attempts]);

        $remaining = self::MAX_ATTEMPTS - $user->attempts;
        $message   = $remaining > 0
            ? "Invalid credentials. {$remaining} attempt(s) remaining."
            : "Too many attempts. Try again in " . self::LOCKOUT_MINUTES . " minutes.";

        $this->throwNotFoundException($message, Response::HTTP_UNAUTHORIZED, false, false, false);
    }

    private function resetAttempts(User $user): void
    {
        $user->attempts        = 0;
        $user->attempts_expiry = null;
        $user->save();
    }

    private function generateEncryptedToken(User $user): string
    {
        $token = JWTAuth::claims(['jti' => Str::uuid()->toString(), 'type' => $user->type])
            ->fromUser($user);

        return openssl_encrypt(
            $token,
            'aes-256-cbc',
            base64_decode(config('app.AES_KEY')),
            0,
            hex2bin(config('app.AES_IV'))
        );
    }

    private function throwNotFoundException(
        string $message = 'Invalid credentials.',
        int $status = Response::HTTP_UNAUTHORIZED,
        bool $global_error = true,
		bool $isShowModal = false,
		bool $isCustomMessage = false,
    ): never {
        $this->throwJsonExceptionService->throwJsonException(
            new ExceptionParametersDTO(
                message: $message,
                status: $status,
                global_error: $global_error,
				is_show_modal: $isShowModal,
				is_custom_message: $isCustomMessage,
            )
        );
    }

}
