<?php

namespace App\Commands;

use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class SetCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:credentials
        {--token= : Timely API key (set non-interactively)}
        {--account-id= : Timely account ID (set non-interactively)}
        {--user-id= : Timely user ID (set non-interactively)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set your Timely API credentials.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = $this->option('token') ?? password(
            label: 'Timely API key',
            hint: UserConfig::getApiToken() ? 'Leave blank to keep the existing key.' : '',
        );

        $accountId = $this->option('account-id') ?? text(
            label: 'Account ID',
            default: (string) config('timely.account_id'),
            required: true,
        );

        $userId = $this->option('user-id') ?? text(
            label: 'User ID',
            default: (string) config('timely.user_id'),
            required: true,
        );

        if (is_string($token) && $token !== '') {
            UserConfig::setApiToken($token);
        }
        if (is_string($accountId) && $accountId !== '') {
            UserConfig::setAccountId($accountId);
        }
        if (is_string($userId) && $userId !== '') {
            UserConfig::setUserId($userId);
        }

        info('Credentials saved.');
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
