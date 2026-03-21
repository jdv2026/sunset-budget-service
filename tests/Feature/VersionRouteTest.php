<?php

namespace Tests\Feature;

use Tests\TestCase;

class VersionRouteTest extends TestCase
{
    public function test_returns_200(): void
    {
        $response = $this->getJson('/api/version');

        $response->assertStatus(200);
    }

    public function test_returns_version_key(): void
    {
        $response = $this->getJson('/api/version');

        $response->assertJsonStructure(['version']);
    }

    public function test_returns_version_from_config(): void
    {
        config(['app.version' => '2.5.1']);

        $response = $this->getJson('/api/version');

        $response->assertJson(['version' => '2.5.1']);
    }

    public function test_requires_no_authentication(): void
    {
        $response = $this->getJson('/api/version');

        $response->assertStatus(200);
    }
}
