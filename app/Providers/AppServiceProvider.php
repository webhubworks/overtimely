<?php

namespace App\Providers;

use App\Services\TimelyService;
use App\Support\UserConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * Merges the user-level config file (~/.config/overtimely/config.json) into Laravel's config repository.
     * Precedence:
     *   env() -> user config file -> config default
     */
    public function boot(): void
    {
        $map = [
            ['timely.token',        'TIMELY_API_TOKEN',  UserConfig::get(UserConfig::API_TOKEN)],
            ['timely.account_id',   'TIMELY_ACCOUNT_ID', UserConfig::get(UserConfig::ACCOUNT_ID)],
            ['timely.user_id',      'TIMELY_USER_ID',    UserConfig::get(UserConfig::USER_ID)],
            ['timely.since',        'TIMELY_SINCE',      UserConfig::get(UserConfig::SINCE)],
            ['display.table_style', 'TABLE_STYLE',       UserConfig::get(UserConfig::TABLE_STYLE)],
        ];

        foreach ($map as [$configKey, $envKey, $userConfigValue]) {
            // An explicit environment variable should always win. So we let the config.php take precedence.
            $env = env($envKey);
            if (is_string($env) && trim($env) !== '') {
                continue;
            }

            if ($userConfigValue !== null && $userConfigValue !== '') {
                config()->set($configKey, $userConfigValue);
            }
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TimelyService::class, function () {
            $config = config('timely');

            $client = Http::baseUrl($config['base_url'])
                ->withToken($config['token'])
                ->acceptJson()
                ->timeout($config['timeout'])
                ->retry(3, 200)
                ->throw();

            return new TimelyService(
                $client,
                $config['account_id'],
                $config['user_id']
            );
        });
    }
}
