<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class Deploy extends Command
{
    protected $signature = 'deploy';

    protected $description = 'Run all deployment steps: migrate, cache, optimize';

    public function handle(): int
    {
        $this->info('Starting deployment...');
        $this->newLine();

        $steps = [
            'Installing Sunset'        => ['php', 'artisan', 'install:sunset'],
            'Running migrations'       => ['php', 'artisan', 'migrate', '--force'],
            'Caching configuration'   => ['php', 'artisan', 'config:cache'],
            'Caching routes'          => ['php', 'artisan', 'route:cache'],
            'Optimizing'              => ['php', 'artisan', 'optimize'],
        ];

        foreach ($steps as $label => $command) {
            if ($this->runStep($label, $command) === self::FAILURE) {
                return self::FAILURE;
            }
        }

        $this->updateDbVersion();

        $this->newLine();
        $this->info('Deployment completed successfully.');

        return self::SUCCESS;
    }

    private function updateDbVersion(): void
    {
        $this->info('→ Computing DB version...');

        $files = glob(database_path('migrations/*.php'));

        $major = 0;
        $minor = 0;
        $patch = 0;

        foreach ($files as $file) {
            $name = strtolower(basename($file));

            if (str_contains($name, 'delete') || str_contains($name, 'drop')) {
                $major++;
            } elseif (str_contains($name, 'create')) {
                $minor++;
            } else {
                $patch++;
            }
        }

        $version     = "{$major}.{$minor}.{$patch}";
        $description = "Major(drop/delete): {$major} | Minor(create): {$minor} | Patch: {$patch}";

        DB::table('db_versions')->updateOrInsert(
            ['version' => $version],
            ['description' => $description, 'updated_at' => now(), 'created_at' => now()]
        );

        $this->info("✓ DB version set to {$version}");
        $this->newLine();
    }

    private function runStep(string $label, array $command): int
    {
        $this->info("→ {$label}...");

        $process = new Process($command, base_path());
        $process->setTimeout(300);
        $process->run(function (string $_, string $output) {
            $this->line(trim($output));
        });

        if (! $process->isSuccessful()) {
            $this->error("Failed: {$label}");
            $this->error($process->getErrorOutput());
            return self::FAILURE;
        }

        $this->info("✓ {$label} done.");
        $this->newLine();

        return self::SUCCESS;
    }
}