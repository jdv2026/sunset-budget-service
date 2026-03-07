<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_root_returns_403(): void
    {
        $response = $this->get('/api/');

        $response->assertStatus(403);
    }
}
