<?php

namespace Tests\Feature;

use App\Http\Middleware\JwtAuthenticate;
use App\Services\MetaService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class NavTest extends TestCase
{
    public function test_requires_auth(): void
    {
        $response = $this->postJson('/api/web/nav');

        $response->assertStatus(401);
    }

    public function test_get_nav_returns_success_with_navigation_data(): void
    {
        $this->withoutMiddleware(JwtAuthenticate::class);

        $fakeNav = Collection::make([
            ['header' => 'Main',   'items' => [['name' => 'Home',     'link' => '/dashboard/home',            'logo' => 'mat:home']]],
            ['header' => 'Budget', 'items' => [['name' => 'Overview', 'link' => '/dashboard/budget/overview', 'logo' => 'mat:dashboard']]],
        ]);

        $this->mock(MetaService::class, function ($mock) use ($fakeNav) {
            $mock->shouldReceive('handleGetNavigationData')
                 ->once()
                 ->andReturn($fakeNav);
        });

        $response = $this->postJson('/api/web/nav');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Success')
                 ->assertJsonPath('payload.0.header', 'Main')
                 ->assertJsonPath('payload.1.header', 'Budget');
    }

    public function test_get_nav_returns_success_when_navigation_is_empty(): void
    {
        $this->withoutMiddleware(JwtAuthenticate::class);

        $this->mock(MetaService::class, function ($mock) {
            $mock->shouldReceive('handleGetNavigationData')
                 ->once()
                 ->andReturn(Collection::make([]));
        });

        $response = $this->postJson('/api/web/nav');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Success')
                 ->assertJsonPath('payload', []);
    }
}
