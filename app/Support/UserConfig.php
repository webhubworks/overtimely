<?php

namespace App\Support;

/**
 * Persists user-level settings (Timely credentials + display preferences) to a
 * single JSON file outside the package install dir, so the values survive
 * `composer global update`/reinstall and work regardless of where the user
 * installed the tool.
 *
 * Location follows the XDG Base Directory spec:
 *   $XDG_CONFIG_HOME/overtimely/config.json
 *   (falls back to ~/.config/overtimely/config.json)
 */
final class UserConfig
{
    public const string ACCOUNT_ID = 'account_id';

    public const string USER_ID = 'user_id';

    public const string SINCE = 'since';

    public const string TABLE_STYLE = 'table_style';

    public const string ACCESS_TOKEN = 'access_token';

    public const string REFRESH_TOKEN = 'refresh_token';

    public const string TOKEN_EXPIRES_AT = 'token_expires_at';

    public const string CLIENT_ID = 'client_id';

    public const string CLIENT_SECRET = 'client_secret';

    public const string REDIRECT_URI = 'redirect_uri';

    public const string CREATED_AT = 'created_at';

    private const array KEYS = [
        self::ACCESS_TOKEN,
        self::REFRESH_TOKEN,
        self::TOKEN_EXPIRES_AT,
        self::CLIENT_ID,
        self::CLIENT_SECRET,
        self::REDIRECT_URI,
        self::ACCOUNT_ID,
        self::USER_ID,
        self::CREATED_AT,
        self::SINCE,
        self::TABLE_STYLE,
    ];

    /**
     * The keys required to reach the Timely API, mapped to the config entry each one is merged into.
     */
    private const array CREDENTIALS = [
        self::REFRESH_TOKEN => 'timely.refresh_token',
        self::ACCOUNT_ID => 'timely.account_id',
        self::USER_ID => 'timely.user_id',
    ];

    public static function path(): string
    {
        $configHome = getenv('XDG_CONFIG_HOME');
        if (! is_string($configHome) || trim($configHome) === '') {
            $home = $_SERVER['HOME'] ?? getenv('HOME') ?: sys_get_temp_dir();
            $configHome = rtrim((string) $home, '/').'/.config';
        }

        return rtrim($configHome, '/').'/overtimely/config.json';
    }

    public static function exists(): bool
    {
        return is_file(self::path());
    }

    /** @return array<string, mixed> */
    public static function load(): array
    {
        if (! self::exists()) {
            return [];
        }
        $content = @file_get_contents(self::path());
        if ($content === false) {
            return [];
        }
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): void
    {
        $path = self::path();
        $dir = dirname($path);
        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Could not create config directory: {$dir}");
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Could not encode config JSON.');
        }
        if (@file_put_contents($path, $json."\n") === false) {
            throw new \RuntimeException("Could not write config file: {$path}");
        }
        // The file may hold the API token, so keep it owner-only.
        @chmod($path, 0600);
    }

    public static function get(string $key): mixed
    {
        self::assertKnown($key);

        $value = self::load()[$key] ?? null;

        return $value === '' ? null : $value;
    }

    public static function set(string $key, mixed $value): void
    {
        self::setMany([$key => $value]);
    }

    public static function setMany(array $values): void
    {
        $data = self::load();

        foreach ($values as $key => $value) {
            self::assertKnown($key);

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === null || $value === '') {
                unset($data[$key]);
            } else {
                $data[$key] = $value;
            }
        }

        self::save($data);
    }

    /**
     * Whether the user config file holds a complete set of credentials.
     */
    public static function isConfigured(): bool
    {
        return array_all(array_keys(self::CREDENTIALS), fn ($key) => filled(self::get($key)));
    }

    /**
     * Whether the necessary credentials are present in the effective configuration,
     * regardless of whether they come from the environment or from the user config file.
     */
    public static function hasCredentials(): bool
    {
        return array_all(self::CREDENTIALS, fn ($configKey) => filled(config($configKey)));
    }

    private static function assertKnown(string $key): void
    {
        if (! in_array($key, self::KEYS, true)) {
            throw new \InvalidArgumentException("Unknown config key: {$key}");
        }
    }
}
