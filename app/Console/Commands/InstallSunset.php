<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallSunset extends Command
{
    protected $signature = 'install:sunset';

    protected $description = 'Generate RSA key pair for JWT and update .env';

    private string $privateKeyPath;
    private string $publicKeyPath;

    public function __construct()
    {
        parent::__construct();
        $this->privateKeyPath = storage_path('jwt/private_rsa.pem');
        $this->publicKeyPath  = storage_path('jwt/public_rsa.pem');
    }

    public function handle(): int
    {
        if (file_exists($this->privateKeyPath) && file_exists($this->publicKeyPath)) {
            $this->info('JWT keys already exist, skipping installation.');
            return self::SUCCESS;
        }

        $this->ensureJwtStorageDirectoryExists();

        $this->info('Generating RSA key pair (JWT signing)...');
        $jwtKey = $this->generateRsaKeyResource();
        if ($jwtKey === false) {
            $this->error('Failed to generate JWT RSA key pair. Ensure the openssl extension is enabled.');
            return self::FAILURE;
        }
        $this->writeKeyFiles($jwtKey, $this->privateKeyPath, $this->publicKeyPath);
        $this->info("Private key → {$this->privateKeyPath}");
        $this->info("Public key  → {$this->publicKeyPath}");
        $this->newLine();
        $this->info('--- JWT Public Key (copy to other services as JWT_PUBLIC_KEY) ---');
        $this->line(file_get_contents($this->publicKeyPath));
        $this->info('-----------------------------------------------------------------');

        $this->writeJwtKeyPathsToEnv();
        $this->writeAesKeysToEnv();
        $this->fixKeyFileOwnership();

        $this->info('.env updated with new JWT key paths and AES keys.');

        return self::SUCCESS;
    }

    private function ensureJwtStorageDirectoryExists(): void
    {
        $directory = dirname($this->privateKeyPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
    }

    private function generateRsaKeyResource(): mixed
    {
        return openssl_pkey_new([
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
    }

    private function writeKeyFiles(mixed $keyResource, string $privatePath, string $publicPath): void
    {
        openssl_pkey_export($keyResource, $privateKey);
        file_put_contents($privatePath, $privateKey);
        if (! chmod($privatePath, 0600)) {
            $this->warn("Could not set permissions on {$privatePath}. Run manually: sudo chmod 0600 {$privatePath}");
        }

        $publicKey = openssl_pkey_get_details($keyResource)['key'];
        file_put_contents($publicPath, $publicKey);
        if (! chmod($publicPath, 0644)) {
            $this->warn("Could not set permissions on {$publicPath}. Run manually: sudo chmod 0644 {$publicPath}");
        }
    }

    private function writeJwtKeyPathsToEnv(): void
    {
        $this->writeEnvValue('JWT_PRIVATE_KEY', 'file://' . $this->privateKeyPath);
        $this->writeEnvValue('JWT_PUBLIC_KEY',  'file://' . $this->publicKeyPath);
    }

    private function writeAesKeysToEnv(): void
    {
        $aesKey = base64_encode(openssl_random_pseudo_bytes(32));
        $aesIv  = strtoupper(bin2hex(openssl_random_pseudo_bytes(16)));

        $this->writeEnvValue('AES_KEY', $aesKey);
        $this->writeEnvValue('AES_IV',  $aesIv);

        $this->info('AES_KEY and AES_IV generated and written to .env.');
    }

    private function fixKeyFileOwnership(): void
    {
        $webUser = 'www-data';
        $files   = [
            $this->privateKeyPath,
            $this->publicKeyPath,
        ];

        $failed = false;

        foreach ($files as $file) {
            if (file_exists($file)) {
                if (! chown($file, $webUser) || ! chgrp($file, $webUser)) {
                    $failed = true;
                }
            }
        }

        if ($failed) {
            $this->warn("Could not set key file ownership to {$webUser}. Run as root or manually execute:");
            $this->warn("  sudo chown {$webUser}:{$webUser} " . implode(' ', $files));
        } else {
            $this->info("Key file ownership set to {$webUser}.");
        }
    }

    private function writeEnvValue(string $key, string $value): void
    {
        $envPath  = base_path('.env');
        $contents = file_get_contents($envPath);
        $pattern  = '/^' . preg_quote($key, '/') . '=.*/m';

        $updated = preg_match($pattern, $contents)
            ? preg_replace($pattern, "{$key}={$value}", $contents)
            : $contents . PHP_EOL . "{$key}={$value}";

        file_put_contents($envPath, $updated);
    }
}