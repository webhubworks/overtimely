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
    case AccessToken = 'timely.access_token';

    case RefreshToken = 'timely.refresh_token';

    case TokenExpiresAt = 'timely.token_expires_at';

    case ClientId = 'timely.oauth.client_id';

    case ClientSecret = 'timely.oauth.client_secret';

    case RedirectUri = 'timely.oauth.redirect_uri';

    case AccountId = 'timely.account_id';

    case UserId = 'timely.user_id';

    case CreatedAt = 'timely.created_at';

    case Since = 'timely.since';

    case TableStyle = 'display.table_style';

    /**
     * The name of the environment variable that overrides the user config value.
     */
    public function envKey(): string
    {
        return strtoupper(str_replace('.', '_', $this->value));
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
        ];
    }
}
