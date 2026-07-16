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
    public const ACCOUNT_ID = 'account_id';

    public const USER_ID = 'user_id';

    public const SINCE = 'since';

    public const TABLE_STYLE = 'table_style';

    public const ACCESS_TOKEN = 'access_token';

    public const REFRESH_TOKEN = 'refresh_token';

    public const TOKEN_EXPIRES_AT = 'token_expires_at';

    public const CLIENT_ID = 'client_id';

    public const CLIENT_SECRET = 'client_secret';

    public const REDIRECT_URI = 'redirect_uri';

    public const CREATED_AT = 'created_at';

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

            if ($value === null || $value === '') {
                unset($data[$key]);
            } else {
                $data[$key] = $value;
            }
        }

        self::save($data);
    }

    public static function isConfigured(): bool
    {
        return self::get(self::REFRESH_TOKEN) !== null
            && self::get(self::ACCOUNT_ID) !== null
            && self::get(self::USER_ID) !== null;
    }

    private static function assertKnown(string $key): void
    {
        if (! in_array($key, self::KEYS, true)) {
            throw new \InvalidArgumentException("Unknown config key: {$key}");
        }
    }
}
