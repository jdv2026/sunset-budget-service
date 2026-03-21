<?php

namespace Tests\Feature;

use App\Services\JwksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetColorCategoryTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/web/budget-color-categories';

    public function test_requires_authentication(): void
    {
        $response = $this->withToken('')->getJson(self::ENDPOINT);

        $response->assertStatus(401);
    }

    public function test_returns_401_for_invalid_token(): void
    {
        $response = $this->withToken('invalid-token')->getJson(self::ENDPOINT);

        $response->assertStatus(401);
    }

    public function test_returns_200_when_authenticated(): void
    {
        $this->mockJwks();

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'payload',
                'status',
            ]);
    }

    public function test_returns_all_seeded_color_categories(): void
    {
        $this->mockJwks();

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $this->assertCount(33, $response->json('payload'));
    }

    public function test_returns_color_categories_sorted_by_name(): void
    {
        $this->mockJwks();

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $names = array_column($response->json('payload'), 'color_name');
        $sorted = $names;
        sort($sorted);

        $this->assertEquals($sorted, $names);
    }

    public function test_each_color_has_hex_code(): void
    {
        $this->mockJwks();

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        foreach ($response->json('payload') as $color) {
            $this->assertArrayHasKey('hex_code', $color);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color['hex_code']);
        }
    }

    private function mockJwks(): void
    {
        $this->mock(JwksService::class, function ($mock) {
            $mock->shouldReceive('verifyToken')
                ->andReturn((object) ['pre_auth' => false]);
        });
    }
}
