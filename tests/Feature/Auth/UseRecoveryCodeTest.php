<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use Tests\TestCase;

class UseRecoveryCodeTest extends TestCase
{
    public function test_fails_validation_when_recovery_code_is_missing(): void
    {
        $response = $this->postJson('/api/web/2fa/recovery', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['recovery_code']);
    }

    public function test_uses_recovery_code_successfully(): void
    {
        $fakeUser = new User(['username' => 'admin']);

        $this->mock(AuthService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('resolvePreAuthUser')->once()->andReturn($fakeUser);
            $mock->shouldReceive('generateToken')->once()->with($fakeUser)->andReturn('final-token');
        });

        $this->mock(TwoFactorService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('useRecoveryCode')->once()->with($fakeUser, 'RECOVERY-CODE-ABC');
        });

        $response = $this->postJson('/api/web/2fa/recovery', [
            'recovery_code' => 'RECOVERY-CODE-ABC',
        ], [
            'Authorization' => 'Bearer pre-auth-token',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Recovery code accepted. 2FA has been disabled.')
                 ->assertJsonPath('payload', null);
    }
}
