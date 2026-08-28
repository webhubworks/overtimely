<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Every setting this app persists, named by the entry it occupies in Laravel's config repository.
 *
 * That same dot path addresses the value inside the user config JSON file,
 * and the environment variable that overrides it is derived from it.
 */
enum ConfigKey: string
{
    public const string DATE_FORMATS_HINT = '(Supported formats: https://www.php.net/manual/en/datetime.formats.php)';

    case AccessToken = 'timely.tokens.access';

    case RefreshToken = 'timely.tokens.refresh';

    case TokenExpiresAt = 'timely.tokens.expires_at';

    case ClientId = 'timely.oauth.client_id';

    case ClientSecret = 'timely.oauth.client_secret';

    case RedirectUri = 'timely.oauth.redirect_uri';

    case AccountId = 'timely.account.id';

    case UserId = 'timely.user.id';

    case UserCreatedAt = 'timely.user.created_at';

    case Since = 'timely.report.since';

    case TableStyle = 'display.table_style';

    /**
     * The name this key goes by on the command line, e.g. `config:set account-id`.
     */
    public function settingName(): string
    {
        return Str::kebab($this->name);
    }

    /**
     * The human-readable name used in prompts and confirmations.
     */
    public function label(): string
    {
        return match ($this) {
            self::AccessToken => 'Access token',
            self::RefreshToken => 'Refresh token',
            self::TokenExpiresAt => 'Token expiry',
            self::ClientId => 'Timely OAuth client ID',
            self::ClientSecret => 'Timely OAuth client secret',
            self::RedirectUri => 'OAuth redirect URI',
            self::AccountId => 'Timely account ID',
            self::UserId => 'Timely user ID',
            self::UserCreatedAt => 'Timely account creation date',
            self::Since => 'Default report start date',
            self::TableStyle => 'Table style',
        };
    }

    /**
     * Whether the value must be masked when it is listed.
     */
    public function isSecret(): bool
    {
        return in_array($this, self::secrets(), true);
    }

    public function isSettable(): bool
    {
        return in_array($this, self::settable(), true);
    }

    public static function fromSettingName(string $name): ?self
    {
        return array_find(self::cases(), fn (self $key): bool => $key->settingName() === $name);
    }

    /**
     * The name of the environment variable that overrides the user config value.
     */
    public function envKey(): string
    {
        return strtoupper(str_replace('.', '_', $this->value));
    }

    /**
     * The value of the environment variable that overrides this key.
     * A variable that is set but blank counts as absent, so the default still applies.
     */
    public function envValue(mixed $default = null): mixed
    {
        $value = env($this->envKey());

        return filled($value) ? $value : $default;
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
            self::ClientId,
            self::ClientSecret,
        ];
    }

    /**
     * The keys a user sets by hand. Everything else is written by the app itself.
     *
     * @return array<self>
     */
    public static function settable(): array
    {
        return [
            self::AccountId,
            self::Since,
            self::TableStyle,
        ];
    }

    public static function secrets(): array
    {
        return [
            self::AccessToken,
            self::RefreshToken,
            self::ClientSecret,
        ];
    }
}
