<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use Tests\TestCase;

class LoginTest extends TestCase
{
    private array $fakeQr = ['qr_code' => 'data:image/svg+xml;base64,...', 'secret' => 'BASE32SECRET'];

    public function test_login_fails_validation_when_fields_are_missing(): void
    {
        $response = $this->postJson('/api/web/user/login', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_login_fails_validation_when_username_too_short(): void
    {
        $response = $this->postJson('/api/web/user/login', [
            'username' => 'ab',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['username']);
    }

    public function test_login_fails_validation_when_password_too_short(): void
    {
        $response = $this->postJson('/api/web/user/login', [
            'username' => 'admin',
            'password' => '123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_login_success_when_2fa_is_disabled(): void
    {
        $fakeUser = new User(['username' => 'admin', 'two_factor_enabled' => false]);

        $this->mock(AuthService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('findUser')->once()->with('admin')->andReturn($fakeUser);
            $mock->shouldReceive('login')->once()->with('admin', 'secret123');
            $mock->shouldReceive('generatePreAuthToken')->once()->with($fakeUser)->andReturn('encrypted-token');
        });

        $this->mock(TwoFactorService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('generateQR')->once()->with($fakeUser)->andReturn($this->fakeQr);
        });

        $response = $this->postJson('/api/web/user/login', [
            'username' => 'admin',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Login successful.')
                 ->assertJsonPath('payload.token', 'encrypted-token')
                 ->assertJsonPath('payload.is_2fa_enabled', false)
                 ->assertJsonPath('payload.qr_code_url.secret', 'BASE32SECRET');
    }

    public function test_login_success_when_2fa_is_enabled(): void
    {
        $fakeUser = new User(['username' => 'admin', 'two_factor_enabled' => true]);

        $this->mock(AuthService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('findUser')->once()->with('admin')->andReturn($fakeUser);
            $mock->shouldReceive('login')->once()->with('admin', 'secret123');
            $mock->shouldReceive('generatePreAuthToken')->once()->with($fakeUser)->andReturn('encrypted-token');
        });

        $this->mock(TwoFactorService::class, function ($mock) use ($fakeUser) {
            $mock->shouldReceive('generateQR')->once()->with($fakeUser)->andReturn($this->fakeQr);
        });

        $response = $this->postJson('/api/web/user/login', [
            'username' => 'admin',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Login successful.')
                 ->assertJsonPath('payload.token', 'encrypted-token')
                 ->assertJsonPath('payload.is_2fa_enabled', true)
                 ->assertJsonPath('payload.qr_code_url.secret', 'BASE32SECRET');
    }
}
