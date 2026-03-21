<?php

namespace App\Http\Middleware;

use App\Services\JwksService;
use Closure;
use Exception;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    public function __construct(private readonly JwksService $jwksService) {}

    public function handle(Request $request, Closure $next): Response
	{
        Log::info('Token validation attempt');

        try {
            $token = $this->jwksService->verifyToken($request->bearerToken());
            if (($token->pre_auth ?? false) === true) {
                return $this->jsonError('Token is invalid');
            }

            $request->attributes->set('jwt_payload', $token);

            return $next($request);
        }
		catch (ExpiredException $e) {
			Log::debug($e);
            return $this->handleExpiredToken($request, $next);
        }
		catch (\Throwable $e) {
			Log::debug($e);
            return $this->jsonError('Token invalid');
        }
    }

    private function handleExpiredToken(Request $request, Closure $next): Response
    {
        Log::info('Token expired, attempting refresh');
        try {
            $newToken = $this->jwksService->refreshToken($request->bearerToken());

            $request->headers->set('Authorization', 'Bearer ' . $newToken);
            $response = $next($request);

            $data = $response->getOriginalContent() ?? [];
            $data['access_token'] = $newToken;

            return response()->json($data, $response->getStatusCode(), $response->headers->all());

        } catch (Exception $e) {
            Log::debug($e);
            return $this->jsonError('Session expired, please login again');
        }
    }

    private function jsonError(string $message): Response
	{
        return response()->json([
            'message' => $message,
            'global_error' => true,
        ], 401);
    }

}
