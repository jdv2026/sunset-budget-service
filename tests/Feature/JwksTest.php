<?php

namespace Tests\Feature;

use Tests\TestCase;

class JwksTest extends TestCase
{
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
