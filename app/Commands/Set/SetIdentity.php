<?php

namespace App\Commands\Set;

use App\Services\TimelyService;
use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;
use Throwable;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;

class SetIdentity extends Command
{
    protected $signature = 'set:identity';

    protected $description = 'Fetches and stores your Timely user ID and account creation date.';

    public function handle(): int
    {
        if (blank(config('timely.account_id'))) {
            $this->error('No Timely account ID set. Run set:account-id first.');

            return self::FAILURE;
        }

        if (blank(config('timely.access_token')) && blank(config('timely.refresh_token'))) {
            $this->error('Not authenticated with Timely. Run auth:login first.');

            return self::FAILURE;
        }

        try {
            $user = app(TimelyService::class)->getCurrentUser();
        } catch (Throwable $e) {
            $this->error('Could not fetch your Timely user: '.$e->getMessage());

            return self::FAILURE;
        }

        $createdAt = $user->createdAt->format('Y-m-d');

        UserConfig::setMany([
            UserConfig::USER_ID => (string) $user->id,
            UserConfig::CREATED_AT => $createdAt,
        ]);

        config()->set('timely.user_id', (string) $user->id);
        config()->set('timely.created_at', $createdAt);

        info("Identified as Timely user {$user->id} (account created {$createdAt}).");
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }
}
