<?php

namespace App\Support;

use App\Enums\ConfigKey;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Persists user-level settings (Timely credentials + display preferences) to a
 * single JSON file outside the package install dir, so the values survive
 * `composer global update`/reinstall and work regardless of where the user
 * installed the tool.
 *
 * The file mirrors the structure of Laravel's config repository, so the key
 * timely.oauth.client_id is stored as {"timely": {"oauth": {"client_id": ...}}}.
 *
 * Location follows the XDG Base Directory spec, using the app name as the directory:
 *   $XDG_CONFIG_HOME/<app.name>/config.json
 *   (falls back to ~/.config/<app.name>/config.json)
 */
final class UserConfig
{
    public static function path(): string
    {
        $configHome = getenv('XDG_CONFIG_HOME');
        $appName = Str::slug(config('app.name'));

        if (! is_string($configHome) || trim($configHome) === '') {
            $home = $_SERVER['HOME'] ?? getenv('HOME') ?: sys_get_temp_dir();
            $configHome = rtrim((string) $home, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.config';
        }

        return rtrim($configHome, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$appName.DIRECTORY_SEPARATOR.'config.json';
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

        return is_array($data) ? Arr::only($data, self::sections()) : [];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws RuntimeException
     */
    public static function save(array $data): void
    {
        $path = self::path();
        $dir = dirname($path);
        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException("Could not create config directory: {$dir}");
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Could not encode config JSON.');
        }
        if (@file_put_contents($path, $json."\n") === false) {
            throw new RuntimeException("Could not write config file: {$path}");
        }
        // The file may hold the API token, so keep it owner-only.
        @chmod($path, 0600);
    }

    public static function get(ConfigKey $key): mixed
    {
        $value = data_get(self::load(), $key->value);

        return $value === '' ? null : $value;
    }

    /**
     * @throws RuntimeException
     */
    public static function set(ConfigKey $key, mixed $value): void
    {
        self::setMany([[$key, $value]]);
    }

    /**
     * @param  list<array{ConfigKey, mixed}>  $values
     *
     * @throws RuntimeException
     */
    public static function setMany(array $values): void
    {
        $data = self::load();

        foreach ($values as [$key, $value]) {
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === null || $value === '') {
                Arr::forget($data, $key->value);
            } else {
                data_set($data, $key->value, $value);
            }
        }

        self::save($data);
    }

    /**
     * Whether every credential the app needs to reach the Timely API is present,
     * regardless of whether it came from the environment or from the user config file.
     */
    public static function isConfigured(): bool
    {
        return array_all(ConfigKey::credentials(), fn (ConfigKey $key) => filled($key->getConfigValue()));
    }

    /**
     * The top-level sections the file may contain. Anything else is dropped on load,
     * which clears settings left behind by an older key layout.
     *
     * @return array<string>
     */
    private static function sections(): array
    {
        return array_values(array_unique(array_map(
            fn (ConfigKey $key): string => Str::before($key->value, '.'),
            ConfigKey::cases()
        )));
    }
}
