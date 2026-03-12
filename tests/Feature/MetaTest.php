<?php

namespace Tests\Feature;

use App\Services\MetaService;
use Tests\TestCase;

class MetaTest extends TestCase
{
    public function test_meta_returns_success_with_db_version(): void
    {
        $fakeVersion = ['version' => 'DB: 1.0.0', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'];

        $this->mock(MetaService::class, function ($mock) use ($fakeVersion) {
            $mock->shouldReceive('handleMetaData')
                 ->once()
                 ->andReturn(['dbVersions' => $fakeVersion]);
        });

        $response = $this->postJson('/api/web/meta/data');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Success')
                 ->assertJsonPath('payload.dbVersions.version', 'DB: 1.0.0');
    }

    public function test_meta_returns_success_when_no_db_version_exists(): void
    {
        $this->mock(MetaService::class, function ($mock) {
            $mock->shouldReceive('handleMetaData')
                 ->once()
                 ->andReturn(['dbVersions' => null]);
        });

        $response = $this->postJson('/api/web/meta/data');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Success')
                 ->assertJsonPath('payload.dbVersions', null);
    }
}
