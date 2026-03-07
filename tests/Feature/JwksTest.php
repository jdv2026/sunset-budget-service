<?php

namespace Tests\Feature;

use Tests\TestCase;

class JwksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $res     = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $details = openssl_pkey_get_details($res);

        $dir = storage_path('jwt');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(storage_path('jwt/public_rsa.pem'), $details['key']);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('jwt/public_rsa.pem'));
        parent::tearDown();
    }

    public function test_returns_jwks_structure(): void
    {
        $response = $this->getJson('/api/jwks');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'payload' => [
                         'keys' => [
                             '*' => ['kty', 'alg', 'use', 'n', 'e'],
                         ],
                     ],
                 ])
                 ->assertJsonPath('payload.keys.0.kty', 'RSA')
                 ->assertJsonPath('payload.keys.0.alg', 'RS256')
                 ->assertJsonPath('payload.keys.0.use', 'sig');
    }
}