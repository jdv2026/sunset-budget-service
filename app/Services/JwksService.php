<?php

namespace App\Services;

use App\DTOs\JwksResponseDTO;
use Exception;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

class JwksService
{
    private const JWKS_CACHE_KEY = 'auth_jwks_keys';
    private const JWKS_CACHE_TTL = 3600;

	public function refreshToken(string $encryptedToken): string
    {
		Log::info('Refreshing token');
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(5)
            ->withToken($encryptedToken)
            ->post(config('app.AUTH_REFRESH_URL'));

        if ($response->status() !== 200) {
            throw new Exception('Failed to refresh token from auth service');
        }

		return $response->json('access_token');
    }

    public function verifyToken(string $token): object
    {
        $jwksDto = $this->getKeys();
        $jwt     = $this->decryptToken($token);
        $key     = array_values(JWK::parseKeySet($jwksDto->toJwksArray()))[0];

        try {
            return JWT::decode($jwt, $key);
        }
        catch (ExpiredException $e) {
            throw $e;
        }
        catch (Exception $e) {
            Log::info('JWT validation failed, refreshing JWKS cache');
            $this->flushCache();
            $key = array_values(JWK::parseKeySet($this->getKeys()->toJwksArray()))[0];
            return JWT::decode($jwt, $key);
        }
    }

	private function decryptToken(String $token): string 
	{
        $keys = $this->getKeys();
		$key = base64_decode($keys->aes->key);
        $iv  = hex2bin($keys->aes->iv);

        $decrypted = openssl_decrypt(
            $token,
            'aes-256-cbc',
            $key,
            0,
            $iv
        );

        if (! $decrypted) {
            throw new TokenInvalidException('Invalid encrypted token');
        }
		Log::debug('Decrypted token: ' . $decrypted);
        return $decrypted;
    }

	private function getKeys(): JwksResponseDTO
    {
        $jwks = Cache::remember(self::JWKS_CACHE_KEY, self::JWKS_CACHE_TTL, function () {
            Log::info('Fetching JWKS from auth service');
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(5)->get(config('app.AUTH_JWKS_URL'));
            if ($response->status() !== 200) {
                throw new Exception('Failed to fetch JWKS from auth service');
            }

            Log::debug('JWKS response', $response->json());
            return $response->json();
        });

        return JwksResponseDTO::fromArray($jwks);
    }

	private function flushCache(): void
    {
        Cache::forget(self::JWKS_CACHE_KEY);
    }

}
