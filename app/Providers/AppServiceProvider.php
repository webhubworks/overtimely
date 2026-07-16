<?php

namespace App\Providers;

use App\Services\TimelyAuth;
use App\Services\TimelyService;
use App\Support\UserConfig;
use Carbon\CarbonImmutable;
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
            ['timely.access_token',       'TIMELY_ACCESS_TOKEN',        UserConfig::get(UserConfig::ACCESS_TOKEN)],
            ['timely.refresh_token',      'TIMELY_REFRESH_TOKEN',       UserConfig::get(UserConfig::REFRESH_TOKEN)],
            ['timely.token_expires_at',   'TIMELY_TOKEN_EXPIRES_AT',    UserConfig::get(UserConfig::TOKEN_EXPIRES_AT)],
            ['timely.oauth.client_id',    'TIMELY_OAUTH_CLIENT_ID',     UserConfig::get(UserConfig::CLIENT_ID)],
            ['timely.oauth.client_secret', 'TIMELY_OAUTH_CLIENT_SECRET', UserConfig::get(UserConfig::CLIENT_SECRET)],
            ['timely.oauth.redirect_uri', 'TIMELY_OAUTH_REDIRECT_URI',  UserConfig::get(UserConfig::REDIRECT_URI)],
            ['timely.account_id',         'TIMELY_ACCOUNT_ID',          UserConfig::get(UserConfig::ACCOUNT_ID)],
            ['timely.user_id',            'TIMELY_USER_ID',             UserConfig::get(UserConfig::USER_ID)],
            ['timely.created_at',         'TIMELY_CREATED_AT',          UserConfig::get(UserConfig::CREATED_AT)],
            ['timely.since',              'TIMELY_SINCE',               UserConfig::get(UserConfig::SINCE)],
            ['display.table_style',       'TABLE_STYLE',                UserConfig::get(UserConfig::TABLE_STYLE)],
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
        $this->app->bind(TimelyService::class, function () {
            $config = config('timely');

            $client = Http::baseUrl($config['base_url'])
                ->withToken(app(TimelyAuth::class)->validAccessToken())
                ->acceptJson()
                ->timeout($config['timeout'])
                ->retry(3, 200)
                ->throw();

            return new TimelyService(
                $client,
                (int) $config['account_id'],
                $config['user_id'] !== null ? (int) $config['user_id'] : null,
                $config['created_at'] !== null
                    ? CarbonImmutable::createFromFormat('!Y-m-d', $config['created_at'])
                    : null,
            );
        });
    }
}
