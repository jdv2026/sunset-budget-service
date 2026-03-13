<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtAuthenticate 
{

    public function handle(Request $request, Closure $next): Response 
	{
        Log::info('Token validation attempt');

        try {
            $token = $this->decryptToken($request);
            $this->authenticateToken($request, $token);

            return $next($request);

        } 
		catch (TokenExpiredException $e) {
            return $this->handleExpiredToken($request, $next);

        } 
		catch (TokenInvalidException $e) {
            return $this->jsonError('Token invalid 2');

        } 
		catch (JWTException $e) {
            return $this->jsonError('Token missing');
        }
    }

    private function decryptToken(Request $request): string 
	{
        $key = base64_decode(config('app.AES_KEY'));
        $iv = hex2bin(config('app.AES_IV'));

        $decrypted = openssl_decrypt(
            $request->bearerToken(),
            'aes-256-cbc',
            $key,
            0,
            $iv
        );

        if (! $decrypted) {
            throw new TokenInvalidException('Invalid encrypted token');
        }

        return $decrypted;
    }

    private function authenticateToken(Request $request, string $token): void 
	{
        $request->headers->set('Authorization', 'Bearer ' . $token);
        JWTAuth::setToken($token)->authenticate();
    }

    private function handleExpiredToken(Request $request, Closure $next): Response 
	{
        Log::error('Token expired, refreshing');

        try {
            $newToken = JWTAuth::refresh($request->bearerToken());

            $request->headers->set('Authorization', 'Bearer ' . $newToken);
            $response = $next($request);

            $data = $response->getOriginalContent() ?? [];
            $data['access_token'] = $this->encryptToken($newToken);

            return response()->json($data, $response->getStatusCode(), $response->headers->all());

        } catch (JWTException $e) {
            return $this->jsonError('Session expired, please login again');
        }
    }

    private function encryptToken(string $token): string 
	{
        $key = base64_decode(config('app.AES_KEY'));
        $iv = hex2bin(config('app.AES_IV'));

        return openssl_encrypt(
            $token,
            'aes-256-cbc',
            $key,
            0,
            $iv
        );
    }

    private function jsonError(string $message): Response 
	{
        return response()->json([
            'message' => $message,
            'global_error' => true,
        ], 401);
    }
	
}
