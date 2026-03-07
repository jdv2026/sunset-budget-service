<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class JwksController extends BaseController
{
    public function jwks(): JsonResponse
    {
        $publicKeyPath = storage_path('jwt/public_rsa.pem');
        $publicKeyPem  = file_get_contents($publicKeyPath);
        $publicKeyRes  = openssl_pkey_get_public($publicKeyPem);
        $keyDetails    = openssl_pkey_get_details($publicKeyRes);

        $n = rtrim(strtr(base64_encode($keyDetails['rsa']['n']), '+/', '-_'), '=');
        $e = rtrim(strtr(base64_encode($keyDetails['rsa']['e']), '+/', '-_'), '=');

        return $this->success([
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'n'   => $n,
                    'e'   => $e,
                ],
            ],
        ]);
    }
}