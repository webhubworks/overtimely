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
     * Merges the user-level config file (~/.config/overtimely/config.json)
     * into Laravel's config repository. An explicit env value (local dev / CI)
     * wins when set; otherwise we fall through to the user config file, and
     * finally to the hardcoded config defaults. Precedence:
     *   env() -> user config file -> config default
     */
    public function boot(): void
    {
        $map = [
            // config key            env var                user config value
            ['timely.token',        'TIMELY_API_TOKEN',     UserConfig::getApiToken()],
            ['timely.account_id',   'TIMELY_ACCOUNT_ID',    UserConfig::getAccountId()],
            ['timely.user_id',      'TIMELY_USER_ID',       UserConfig::getUserId()],
            ['timely.since',        'TIMELY_SINCE',         UserConfig::getSince()],
            ['display.table_style', 'TABLE_STYLE',          UserConfig::getTableStyle()],
        ];

        foreach ($map as [$configKey, $envKey, $userValue]) {
            // An explicitly-set env var always wins (dev / CI override).
            $env = env($envKey);
            if (is_string($env) && trim($env) !== '') {
                continue;
            }

            if ($userValue !== null && $userValue !== '') {
                config()->set($configKey, $userValue);
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
