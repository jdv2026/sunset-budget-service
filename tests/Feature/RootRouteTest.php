<?php

namespace Tests\Feature;

use Tests\TestCase;

class RootRouteTest extends TestCase
{
    public function test_returns_403_status(): void
    {
        $response = $this->get('/api');

        $response->assertStatus(403);
    }

    public function test_renders_forbidden_title(): void
    {
        $response = $this->get('/api');

        $response->assertSee('403 - Forbidden');
    }

    public function test_renders_forbidden_description(): void
    {
        $response = $this->get('/api');

        $response->assertSee('You do not have permission to access this resource.');
    }
}
