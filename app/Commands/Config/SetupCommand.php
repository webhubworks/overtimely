<?php

namespace App\Commands\Config;

use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;

class SetupCommand extends Command
{
    protected $signature = 'config:setup';

    protected $description = 'Interactively configures this app by running each setup step in turn.';

    private const array SETUP_STEPS = [
        'auth:login',
        'config:set account-id',
        'auth:whoami',
        'config:set since',
        'config:set table-style',
    ];

    public function handle(): int
    {
        if (UserConfig::isConfigured()) {
            info('The app is already fully configured. Update the values below or press enter to keep them.');
        } else {
            info('Running setup:');
        }

        foreach (self::SETUP_STEPS as $step) {
            if ($this->call($step) !== self::SUCCESS) {
                $this->warn("Setup aborted at {$step}.");

                return self::FAILURE;
            }
        }

        note('Configuration saved to '.UserConfig::path());

        return self::SUCCESS;
    }
}
