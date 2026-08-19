<?php

namespace App\Providers;

use App\Enums\ConfigKey;
use App\Services\TimelyAuthService;
use App\Services\TimelyDataService;
use App\Support\UserConfig;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * Merges the user-level config file (~/.config/<app.name>/config.json) into Laravel's config repository.
     * Precedence:
     *   env() -> user config file -> config default
     */
    public function boot(): void
    {
        foreach (ConfigKey::cases() as $key) {
            // An explicit environment variable should always win. So we let the config.php take precedence.
            if (filled($key->envValue())) {
                continue;
            }

            $userConfigValue = UserConfig::get($key);
            if (filled($userConfigValue)) {
                $key->setConfigValue($userConfigValue);
            }
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TimelyDataService::class, function () {
            $client = Http::baseUrl(config('timely.base_url'))
                ->withToken(app(TimelyAuthService::class)->validAccessToken())
                ->acceptJson()
                ->timeout(config('timely.timeout'))
                ->retry(3, 200)
                ->throw();

            return new TimelyDataService(
                $client,
                $this->requireNumericId(
                    ConfigKey::AccountId->getConfigValue(),
                    'No valid Timely account ID set. Run set:account-id first.'
                ),
                filled(ConfigKey::UserId->getConfigValue())
                    ? $this->requireNumericId(
                        ConfigKey::UserId->getConfigValue(),
                        'No valid Timely user ID set. Run auth:whoami first.'
                    )
                    : null,
                filled(ConfigKey::CreatedAt->getConfigValue())
                    ? CarbonImmutable::createFromFormat('!Y-m-d', ConfigKey::CreatedAt->getConfigValue())
                    : null,
            );
        });
    }

    /**
     * @throws RuntimeException
     */
    private function requireNumericId(mixed $value, string $message): int
    {
        if (! ctype_digit((string) $value)) {
            throw new RuntimeException($message);
        }

        return (int) $value;
    }
}
