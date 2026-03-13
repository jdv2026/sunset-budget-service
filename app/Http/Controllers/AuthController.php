<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\TwoFactorRequest;
use App\Http\Requests\RecoveryCodeRequest;
use App\Http\Requests\VerifyFirstTime2faRequest;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly TwoFactorService $twoFactorService,
    ) {
    }

    public function userLogin(AdminLoginRequest $request): JsonResponse
    {
        Log::info('User login attempt', ['ip' => $request->ip()]);

        $user = $this->authService->findUser($request->username);
        $this->authService->login($request->username, $request->password);

        Log::info('User login successful', ['username' => $user->username, 'ip' => $request->ip()]);

        return $this->success(
            [
                'token' => $this->authService->generatePreAuthToken($user),
                'is_2fa_enabled' => (bool) $user->two_factor_enabled,
				'qr_code_url' => $this->twoFactorService->generateQR($user),
            ],
            'Login successful.'
        );
    }

    public function enable2fa(TwoFactorRequest $request): JsonResponse
    {
        Log::info('2FA enable attempt', ['ip' => $request->ip()]);

        $user = JWTAuth::user();
        $this->twoFactorService->enable($user, $request->otp);

        Log::info('2FA enabled', ['username' => $user->username]);

        $codes = $this->twoFactorService->createRecoveryCodes($user);

        return $this->success(
            ['recovery_codes' => $codes],
            '2FA enabled successfully. Store your recovery codes safely.'
        );
    }

    public function disable2fa(TwoFactorRequest $request): JsonResponse
    {
        Log::info('2FA disable attempt', ['ip' => $request->ip()]);

        $user = JWTAuth::user();
        $this->twoFactorService->disable($user, $request->otp);

        Log::info('2FA disabled', ['username' => $user->username]);

        return $this->success(null, '2FA disabled successfully.');
    }

    public function verify2fa(TwoFactorRequest $request): JsonResponse
    {
        Log::info('2FA verify attempt', ['ip' => $request->ip()]);

        $user  = $this->authService->resolvePreAuthUser($request->bearerToken());
        $this->twoFactorService->verify($user, $request->otp);
        $token = $this->authService->generateToken($user);

        Log::info('2FA verified', ['username' => $user->username]);

        return $this->success(['token' => $token, 'user' => $user], '2FA verified successfully.');
    }

    public function useRecoveryCode(RecoveryCodeRequest $request): JsonResponse
    {
        Log::info('Recovery code use attempt', ['ip' => $request->ip()]);

        $user  = $this->authService->resolvePreAuthUser($request->bearerToken());
        $this->twoFactorService->useRecoveryCode($user, $request->recovery_code);
        $this->authService->generateToken($user);

        Log::info('Recovery code used, 2FA disabled', ['username' => $user->username]);

        return $this->success(null, 'Recovery code accepted. 2FA has been disabled.');
    }

    public function verifyFirstTime2fa(VerifyFirstTime2faRequest $request): JsonResponse
    {
        Log::info('First-time 2FA verify attempt', ['ip' => $request->ip()]);

        $user = $this->authService->resolvePreAuthUser($request->bearerToken());
        $user->two_factor_secret = $request->secret;
        $this->twoFactorService->verify($user, $request->otp);
        $this->twoFactorService->updateSecret($user, $request->secret);
        $codes = $this->twoFactorService->createRecoveryCodes($user);
        $token = $this->authService->generateToken($user);

        Log::info('First-time 2FA verified', ['username' => $user->username]);

        return $this->success(
            ['token' => $token, 'user' => $user, 'recovery_codes' => $codes],
            '2FA verified successfully.'
        );
    }

	public function fetchAuthUser(): JsonResponse
	{
		Log::info('Fetching authenticated user', ['ip' => request()->ip()]);
		$user = JWTAuth::user();
		return $this->success(['user' => $user, 'settings' => null], 'User fetched successfully.');
	}

}
