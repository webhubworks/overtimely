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

    public static function getBaseUrl(): ?string
    {
        return self::getString('base_url');
    }

    public static function setBaseUrl(string $baseUrl): void
    {
        self::put('base_url', $baseUrl);
    }

    public static function getApiToken(): ?string
    {
        return self::getString('api_token');
    }

    public static function setApiToken(string $token): void
    {
        self::put('api_token', $token);
    }

    public static function getAccountId(): ?string
    {
        return self::getString('account_id');
    }

    public static function setAccountId(string $accountId): void
    {
        self::put('account_id', $accountId);
    }

    public static function getUserId(): ?string
    {
        return self::getString('user_id');
    }

    public static function setUserId(string $userId): void
    {
        self::put('user_id', $userId);
    }

    public static function getSince(): ?string
    {
        return self::getString('since');
    }

    public static function setSince(?string $since): void
    {
        self::put('since', $since !== null && $since !== '' ? $since : null);
    }

    public static function getTableStyle(): ?string
    {
        return self::getString('table_style');
    }

    public static function setTableStyle(string $tableStyle): void
    {
        self::put('table_style', $tableStyle);
    }

    /**
     * True once the Timely credentials required to talk to the API are set.
     */
    public static function isConfigured(): bool
    {
        return self::getApiToken() !== null
            && self::getAccountId() !== null
            && self::getUserId() !== null;
    }

    private static function getString(string $key): ?string
    {
        $value = self::load()[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function put(string $key, mixed $value): void
    {
        $data = self::load();

        if ($value === null) {
            unset($data[$key]);
        } else {
            $data[$key] = $value;
        }

        self::save($data);
    }
}
