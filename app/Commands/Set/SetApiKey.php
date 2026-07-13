<?php

namespace App\Commands\Set;

use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;

class SetApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:api-key {key? : Timely API key. [non-interactive]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets your Timely API key.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $key = $this->argument('key') ?? password(
            label: 'Timely API key',
            required: UserConfig::getApiToken() === null,
            hint: UserConfig::getApiToken() ? 'Leave blank to keep the existing key.' : '',
        );

        if (is_string($key) && $key !== '') {
            UserConfig::setApiToken($key);
            info('API key saved.');
        } else {
            info('API key unchanged.');
        }

        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
