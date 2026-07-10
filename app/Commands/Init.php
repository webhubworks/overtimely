<?php

namespace App\Commands;

use App\Support\UserConfig;
use Carbon\CarbonImmutable;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class Init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively set up your overtimely configuration.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (UserConfig::isConfigured()) {
            info('overtimely is already configured. Update the values below or press enter to keep them.');
        } else {
            info("Let's set up overtimely.");
        }

        $baseUrl = text(
            label: 'Timely API URL',
            default: (string) config('timely.base_url'),
            required: true,
        );

        $token = password(
            label: 'Timely API key',
            hint: UserConfig::getApiToken() ? 'Leave blank to keep the existing key.' : '',
        );

        $accountId = text(
            label: 'Account ID',
            default: (string) config('timely.account_id'),
            required: true,
        );

        $userId = text(
            label: 'User ID',
            default: (string) config('timely.user_id'),
            required: true,
        );

        $since = text(
            label: 'Report start date (Y-m-d)',
            default: (string) config('timely.since'),
            placeholder: '2026-01-01',
            validate: fn (string $value): ?string => $value === '' || CarbonImmutable::hasFormat($value, 'Y-m-d')
                ? null
                : 'Use the format Y-m-d, e.g. 2026-01-01.',
        );

        $tableStyle = select(
            label: 'Table style',
            options: SetTableStyle::STYLES,
            default: config('display.table_style'),
        );

        UserConfig::setBaseUrl($baseUrl);
        UserConfig::setAccountId($accountId);
        UserConfig::setUserId($userId);
        UserConfig::setSince($since);
        UserConfig::setTableStyle($tableStyle);

        if ($token !== '') {
            UserConfig::setApiToken($token);
        }

        note('Configuration saved to '.UserConfig::path());

        return self::SUCCESS;
    }
}
