<?php

namespace App\Enums;

/**
 * Every setting this app persists, named by the entry it occupies in Laravel's config repository.
 *
 * That same dot path addresses the value inside the user config JSON file,
 * and the environment variable that overrides it is derived from it.
 */
enum ConfigKey: string
{
    case AccessToken = 'timely.tokens.access';

    case RefreshToken = 'timely.tokens.refresh';

    case TokenExpiresAt = 'timely.tokens.expires_at';

    case OAuthClientId = 'timely.oauth.client_id';

    case OAuthClientSecret = 'timely.oauth.client_secret';

    case OAuthRedirectUri = 'timely.oauth.redirect_uri';

    case AccountId = 'timely.account.id';

    case UserId = 'timely.user.id';

    case CreatedAt = 'timely.user.created_at';

    case Since = 'timely.report.since';

    case TableStyle = 'display.table_style';

    /**
     * The name of the environment variable that overrides the user config value.
     */
    public function envKey(): string
    {
        return strtoupper(str_replace('.', '_', $this->value));
    }

    /**
     * The value of the environment variable that overrides this key.
     */
    public function envValue(mixed $default = null): mixed
    {
        return env($this->envKey(), $default);
    }

    /**
     * The value this key currently holds in the config repository.
     */
    public function getConfigValue(mixed $default = null): mixed
    {
        return config($this->value, $default);
    }

    /**
     * Overrides this key in the config repository for the rest of the run.
     */
    public function setConfigValue(mixed $value): void
    {
        config()->set($this->value, $value);
    }

    /**
     * The keys required to reach the Timely API.
     *
     * @return array<self>
     */
    public static function credentials(): array
    {
        return [
            self::RefreshToken,
            self::AccountId,
            self::UserId,
            self::OAuthClientId,
            self::OAuthClientSecret,
        ];
    }
}
