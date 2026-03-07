<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\JwtAuthenticate;
use App\Models\User;
use App\Services\TwoFactorService;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class Enable2faTest extends TestCase
{
    public function test_requires_auth(): void
    {
        $response = $this->postJson('/api/web/2fa/enable', ['otp' => '123456']);

        $response->assertStatus(401);
    }

    public function test_fails_validation_when_otp_is_missing(): void
    {
        $this->withoutMiddleware(JwtAuthenticate::class);
        JWTAuth::shouldReceive('user')->andReturn(new User(['username' => 'admin']));

        $response = $this->postJson('/api/web/2fa/enable', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['otp']);
    }

    public function test_fails_validation_when_otp_is_not_6_digits(): void
    {
        $this->withoutMiddleware(JwtAuthenticate::class);
        JWTAuth::shouldReceive('user')->andReturn(new User(['username' => 'admin']));

        $response = $this->postJson('/api/web/2fa/enable', ['otp' => '123']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['otp']);
    }

    public function test_enables_2fa_successfully(): void
    {
        $this->withoutMiddleware(JwtAuthenticate::class);
        $fakeUser = new User(['username' => 'admin']);
        JWTAuth::shouldReceive('user')->andReturn($fakeUser);

        $this->mock(TwoFactorService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('enable')->once()->with($fakeUser, '123456');
            $mock->shouldReceive('createRecoveryCodes')->once()->with($fakeUser)->andReturn(['CODE1', 'CODE2']);
        });

        $response = $this->postJson('/api/web/2fa/enable', ['otp' => '123456']);

        $response->assertStatus(200)
                 ->assertJsonPath('message', '2FA enabled successfully. Store your recovery codes safely.')
                 ->assertJsonPath('payload.recovery_codes', ['CODE1', 'CODE2']);
    }
}
