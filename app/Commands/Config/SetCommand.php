<?php

namespace App\Commands\Config;

use App\Enums\ConfigKey;
use App\Enums\TableStyle;
use App\Support\UserConfig;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class SetCommand extends Command
{
    protected $signature = 'config:set {setting? : The setting to change. [non-interactive]} {value? : The value to store. [non-interactive]}';

    protected $description = 'Sets one of your settings. Run it without arguments to pick a setting interactively.';

    public function handle(): int
    {
        $key = $this->resolveSetting();

        if ($key === null) {
            return self::FAILURE;
        }

        $value = $this->argument('value') ?? $this->askForValue($key);

        if ($value === null) {
            return self::FAILURE;
        }

        $error = $this->validationError($key, $value);

        if ($error !== null) {
            $this->error($error);

            return self::FAILURE;
        }

        $value = $this->cast($key, $value);

        UserConfig::set($key, $value);
        $key->setConfigValue($value);

        info("{$key->label()} set to {$value}.");
        note('Config file: '.UserConfig::path());

        return self::SUCCESS;
    }

    private function resolveSetting(): ?ConfigKey
    {
        $name = $this->argument('setting');

        if ($name === null) {
            if (! $this->input->isInteractive()) {
                $this->error('No setting given. '.$this->settableHint());

                return null;
            }

            $name = select(
                label: 'Which setting do you want to change?',
                options: collect(ConfigKey::settable())
                    ->mapWithKeys(fn (ConfigKey $key): array => [$key->settingName() => $key->label()])
                    ->all(),
            );
        }

        $key = ConfigKey::fromSettingName($name);

        if ($key === null) {
            $this->error("Unknown setting '{$name}'. ".$this->settableHint());

            return null;
        }

        if (! $key->isSettable()) {
            $this->error("The {$key->settingName()} setting is managed by the app and cannot be set by hand. ".$this->settableHint());

            return null;
        }

        return $key;
    }

    private function askForValue(ConfigKey $key): ?string
    {
        if (! $this->input->isInteractive()) {
            $this->error("No value given for the {$key->settingName()} setting.");

            return null;
        }

        return match ($key) {
            ConfigKey::AccountId => text(
                label: $key->label(),
                default: (string) $key->getConfigValue(),
                required: true,
                validate: fn (string $value): ?string => $this->validationError($key, $value),
            ),
            ConfigKey::Since => text(
                label: $key->label().' '.ConfigKey::DATE_FORMATS_HINT,
                placeholder: 'first day of this month',
                default: (string) $key->getConfigValue(),
                validate: fn (string $value): ?string => $this->validationError($key, $value),
            ),
            ConfigKey::TableStyle => select(
                label: 'Select a table style',
                options: TableStyle::values(),
                default: $key->getConfigValue(),
            ),
        };
    }

    private function validationError(ConfigKey $key, string $value): ?string
    {
        return match ($key) {
            ConfigKey::AccountId => ctype_digit($value)
                ? null
                : 'The account ID must be numeric.',
            ConfigKey::Since => strtotime($value) === false
                ? 'Unsupported format. '.ConfigKey::DATE_FORMATS_HINT
                : null,
            ConfigKey::TableStyle => TableStyle::tryFrom($value) === null
                ? "Unknown table style '{$value}'. Choose one of: ".implode(', ', TableStyle::values()).'.'
                : null,
        };
    }

    private function cast(ConfigKey $key, string $value): int|string
    {
        return $key === ConfigKey::AccountId ? (int) $value : $value;
    }

    private function settableHint(): string
    {
        return 'Choose one of: '.collect(ConfigKey::settable())
            ->map(fn (ConfigKey $key): string => $key->settingName())
            ->implode(', ').'.';
    }
}
