<?php

namespace App\Providers;

use App\Services\TimelyAuthService;
use App\Services\TimelyDataService;
use App\Support\UserConfig;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const array CONFIG_MAP = [
        ['timely.access_token',         'TIMELY_ACCESS_TOKEN',        UserConfig::ACCESS_TOKEN],
        ['timely.refresh_token',        'TIMELY_REFRESH_TOKEN',       UserConfig::REFRESH_TOKEN],
        ['timely.token_expires_at',     'TIMELY_TOKEN_EXPIRES_AT',    UserConfig::TOKEN_EXPIRES_AT],
        ['timely.oauth.client_id',      'TIMELY_OAUTH_CLIENT_ID',     UserConfig::CLIENT_ID],
        ['timely.oauth.client_secret',  'TIMELY_OAUTH_CLIENT_SECRET', UserConfig::CLIENT_SECRET],
        ['timely.oauth.redirect_uri',   'TIMELY_OAUTH_REDIRECT_URI',  UserConfig::REDIRECT_URI],
        ['timely.account_id',           'TIMELY_ACCOUNT_ID',          UserConfig::ACCOUNT_ID],
        ['timely.user_id',              'TIMELY_USER_ID',             UserConfig::USER_ID],
        ['timely.created_at',           'TIMELY_CREATED_AT',          UserConfig::CREATED_AT],
        ['timely.since',                'TIMELY_SINCE',               UserConfig::SINCE],
        ['display.table_style',         'TABLE_STYLE',                UserConfig::TABLE_STYLE],
    ];

    /**
     * Bootstrap any application services.
     *
     * Merges the user-level config file (~/.config/overtimely/config.json) into Laravel's config repository.
     * Precedence:
     *   env() -> user config file -> config default
     */
    public function boot(): void
    {
        foreach (self::CONFIG_MAP as [$configKey, $envKey, $userConfigKey]) {
            // An explicit environment variable should always win. So we let the config.php take precedence.
            $envValue = env($envKey);
            if (filled($envValue)) {
                continue;
            }

            $userConfigValue = UserConfig::get($userConfigKey);
            if (filled($userConfigValue)) {
                config()->set($configKey, $userConfigValue);
            }
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TimelyDataService::class, function () {
            $config = config('timely');

            $client = Http::baseUrl($config['base_url'])
                ->withToken(app(TimelyAuthService::class)->validAccessToken())
                ->acceptJson()
                ->timeout($config['timeout'])
                ->retry(3, 200)
                ->throw();

            return new TimelyDataService(
                $client,
                $config['account_id'],
                filled($config['user_id']) ? $config['user_id'] : null,
                filled($config['created_at'])
                    ? CarbonImmutable::createFromFormat('!Y-m-d', $config['created_at'])
                    : null,
            );
        });
    }
}
