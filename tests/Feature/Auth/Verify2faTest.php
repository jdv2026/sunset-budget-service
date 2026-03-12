<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use Tests\TestCase;

class Verify2faTest extends TestCase
{
    public function test_fails_validation_when_otp_is_missing(): void
    {
        $response = $this->postJson('/api/web/2fa/verify', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['otp']);
    }

    public function test_fails_validation_when_otp_is_not_6_digits(): void
    {
        $response = $this->postJson('/api/web/2fa/verify', ['otp' => '123']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['otp']);
    }

    public function test_verifies_2fa_successfully(): void
    {
        $fakeUser = new User(['username' => 'admin']);

        $this->mock(AuthService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('resolvePreAuthUser')->once()->andReturn($fakeUser);
            $mock->shouldReceive('generateToken')->once()->with($fakeUser)->andReturn('final-token');
        });

        $this->mock(TwoFactorService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('verify')->once()->with($fakeUser, '123456');
        });

        $response = $this->postJson('/api/web/2fa/verify', ['otp' => '123456'], [
            'Authorization' => 'Bearer pre-auth-token',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', '2FA verified successfully.')
                 ->assertJsonPath('payload.token', 'final-token');
    }
}