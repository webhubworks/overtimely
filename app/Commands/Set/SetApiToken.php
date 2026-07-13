<?php

namespace App\Commands\Set;

use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;

class SetApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:api-token {token? : Timely API token. [non-interactive]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets your Timely API token.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = $this->argument('token') ?? password(
            label: 'Timely API token',
            required: UserConfig::getApiToken() === null,
            hint: UserConfig::getApiToken() ? 'Leave blank to keep the existing token.' : '',
        );

        if (is_string($token) && $token !== '') {
            UserConfig::setApiToken($token);
            info('API token saved.');
        } else {
            info('API token unchanged.');
        }

        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
