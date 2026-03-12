<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use Tests\TestCase;

class VerifyFirstTime2faTest extends TestCase
{
    public function test_fails_validation_when_fields_are_missing(): void
    {
        $response = $this->postJson('/api/web/firsttime/2fa/verify', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['otp', 'secret']);
    }

    public function test_fails_validation_when_otp_is_not_6_digits(): void
    {
        $response = $this->postJson('/api/web/firsttime/2fa/verify', [
            'otp'    => '123',
            'secret' => 'BASE32SECRET',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['otp']);
    }

    public function test_verifies_first_time_2fa_successfully(): void
    {
        $fakeUser = new User(['username' => 'admin']);

        $this->mock(AuthService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('resolvePreAuthUser')->once()->andReturn($fakeUser);
            $mock->shouldReceive('generateToken')->once()->with($fakeUser)->andReturn('final-token');
        });

        $this->mock(TwoFactorService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('verify')->once()->with($fakeUser, '123456');
            $mock->shouldReceive('updateSecret')->once()->with($fakeUser, 'BASE32SECRET');
            $mock->shouldReceive('createRecoveryCodes')->once()->with($fakeUser)->andReturn(['CODE1', 'CODE2']);
        });

        $response = $this->postJson('/api/web/firsttime/2fa/verify', [
            'otp'    => '123456',
            'secret' => 'BASE32SECRET',
        ], [
            'Authorization' => 'Bearer pre-auth-token',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', '2FA verified successfully.')
                 ->assertJsonPath('payload.token', 'final-token')
                 ->assertJsonPath('payload.recovery_codes', ['CODE1', 'CODE2']);
    }
}