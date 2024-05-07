<?php

namespace Nanicas\Auth\Frameworks\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Nanicas\Auth\Helpers\LaravelAuthHelper;

class GeneratePersonalToken extends Command
{
    protected $signature = 'personal_token:generate {tokenable_type} {--name=access_token} {--abilities=*} {--expires_at=}';

    protected $description = 'Generate a personal access token';

    public function handle()
    {
        $tokenableType = $this->argument('tokenable_type');
        $name = $this->option('name');
        $abilities = $this->option('abilities');
        $expiresAt = $this->getExpiresAt($this->option('expires_at'));

        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);
        $model = app($config['DEFAULT_PERSONAL_TOKEN_MODEL']);

        $lastToken = $model->where('tokenable_type', $tokenableType)->latest()->first();
        if (empty($lastToken)) {
            $tokenableId = 1;
        } else {
            $tokenableId = $lastToken->tokenable_id + 1;
        }

        $token = hash('sha256', random_bytes(32));

        if (empty($abilities)) {
            $abilities = ['*']; // Todas as habilidades
        }

        $personalToken = $model->create([
            'tokenable_type' => $tokenableType,
            'tokenable_id' => $tokenableId,
            'name' => $name,
            'token' => $token,
            'abilities' => json_encode($abilities),
            'expires_at' => $expiresAt,
        ]);

        $this->info('Personal token generated successfully.');
        $this->info('Token: ' . $personalToken->token);
    }

    private function getExpiresAt($expiresAt = null): string
    {
        try {
            if ($expiresAt) {
                $expiresAt = date_create_from_format('Y-m-d H:i:s', $expiresAt);
                if (!$expiresAt) {
                    throw new Exception('Invalid date format');
                }
                $expiresAt = $expiresAt->format('Y-m-d H:i:s');
            } else {
                $expiresAt = now()->addYear()->format('Y-m-d H:i:s');
            }
        } catch (Exception $e) {
            $this->warn('Exception: ' . $e->getMessage());
            $this->warn('Invalid date format. Using default expiration date (one year from now).');
            $expiresAt = now()->addYear()->format('Y-m-d H:i:s');
        }

        return $expiresAt;
    }
}
