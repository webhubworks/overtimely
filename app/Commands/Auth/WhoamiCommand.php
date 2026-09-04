<?php

namespace App\Commands\Auth;

use App\Enums\Setting;
use App\Services\TimelyDataService;
use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;
use Throwable;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;

class WhoamiCommand extends Command
{
    protected $signature = 'auth:whoami';

    protected $description = 'Fetches and stores your Timely user ID and account creation date.';

    public function handle(): int
    {
        if (blank(Setting::AccountId->getConfigValue())) {
            $this->error('No Timely account ID set. Run config:set account-id first.');

            return self::FAILURE;
        }

        if (blank(Setting::AccessToken->getConfigValue()) && blank(Setting::RefreshToken->getConfigValue())) {
            $this->error('Not authenticated with Timely. Run auth:login first.');

            return self::FAILURE;
        }

        try {
            $user = app(TimelyDataService::class)->getCurrentUser();
        } catch (Throwable $e) {
            $this->error('Could not fetch your Timely user: '.$e->getMessage());

            return self::FAILURE;
        }

        $createdAt = $user->createdAt->format('Y-m-d');

        UserConfig::setMany([
            [Setting::UserId, $user->id],
            [Setting::UserCreatedAt, $createdAt],
        ]);

        Setting::UserId->setConfigValue($user->id);
        Setting::UserCreatedAt->setConfigValue($createdAt);

        info("Identified as Timely user {$user->id} (account created {$createdAt}).");
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
