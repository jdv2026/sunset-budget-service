<?php

namespace Tests\Feature;

use App\Console\Commands\Deploy;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlledDeploy extends Deploy
{
    public array $stepResults   = [];
    public array $executedSteps = [];

    protected function runStep(string $label, array $command): int
    {
        $this->executedSteps[] = $label;

        return array_shift($this->stepResults) ?? Command::SUCCESS;
    }
}

class DeployTest extends TestCase
{
    use RefreshDatabase;

    private const STEPS = [
        'Installing Sunset',
        'Caching configuration',
        'Caching routes',
        'Optimizing',
        'Running migrations',
    ];

    private ControlledDeploy $deploy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deploy = new ControlledDeploy();
        $this->app->bind(Deploy::class, fn () => $this->deploy);
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function test_returns_success_when_all_steps_pass(): void
    {
        $this->deploy->stepResults = array_fill(0, 5, Command::SUCCESS);

        $this->artisan('deploy')->assertSuccessful();
    }

    public function test_all_five_steps_are_executed_on_success(): void
    {
        $this->deploy->stepResults = array_fill(0, 5, Command::SUCCESS);

        $this->artisan('deploy');

        $this->assertCount(5, $this->deploy->executedSteps);
    }

    public function test_db_version_is_updated_after_successful_deploy(): void
    {
        $this->deploy->stepResults = array_fill(0, 5, Command::SUCCESS);

        $this->artisan('deploy');

        $this->assertDatabaseCount('db_versions', 1);
    }

    // -------------------------------------------------------------------------
    // Abort on failure
    // -------------------------------------------------------------------------

    public function test_returns_failure_when_a_step_fails(): void
    {
        $this->deploy->stepResults = [Command::FAILURE];

        $this->artisan('deploy')->assertFailed();
    }

    public function test_aborts_immediately_when_first_step_fails(): void
    {
        $this->deploy->stepResults = [Command::FAILURE];

        $this->artisan('deploy');

        $this->assertCount(1, $this->deploy->executedSteps);
    }

    public function test_aborts_immediately_when_middle_step_fails(): void
    {
        $this->deploy->stepResults = [
            Command::SUCCESS,
            Command::SUCCESS,
            Command::FAILURE,
        ];

        $this->artisan('deploy');

        $this->assertCount(3, $this->deploy->executedSteps);
    }

    public function test_remaining_steps_do_not_run_after_failure(): void
    {
        $this->deploy->stepResults = [
            Command::SUCCESS,
            Command::FAILURE,
        ];

        $this->artisan('deploy');

        // Only 2 of 5 steps should have run
        $this->assertCount(2, $this->deploy->executedSteps);
    }

    public function test_db_version_is_not_updated_when_deploy_fails(): void
    {
        $this->deploy->stepResults = [Command::FAILURE];

        $this->artisan('deploy');

        $this->assertDatabaseCount('db_versions', 0);
    }
}
