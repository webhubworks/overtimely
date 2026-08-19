# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.0.0 - 2026-08-20
### Added
- Added the `-p|--period` option to every `balance:*` command. It takes one of `this-week`, `last-week`, `this-month`, `last-month`, `this-year`, `last-year` and cannot be combined with `--since`/`--until`. Presets are now available on the list commands as well, so `balance:weekly --period=last-month` works.

### Changed
- **BREAKING:** Renamed the report commands to a consistent `<subject>:<action>` scheme:
  - `get:total` => `balance:total`
  - `get:list:weeks` => `balance:weekly`
  - `get:list:months` => `balance:monthly`
- **BREAKING:** Replaced the four period wrapper commands with the `--period` option:
  - `get:total:this-week` => `balance:total --period=this-week`
  - `get:total:last-week` => `balance:total --period=last-week`
  - `get:total:this-month` => `balance:total --period=this-month`
  - `get:total:last-month` => `balance:total --period=last-month`
- **BREAKING:** Changed the structure of the config file. After the upgrade to `2.0.0` your existing config file cannot be understood by the app anymore. **Please make a copy of your current settings in the file** and delete the file. **After that you will need to run `app:setup` again** and enter your recorded settings accordingly so the config file gets recreated with the new structure.
- `set:table-style` now pre-selects the table style currently in use.

### Fixed
- Fixed the previous calendar month being reported as the current one when the command ran on the 29th, 30th or 31st of a month (the former `get:total:last-month`). The same overflow affected year boundaries on leap days.

## 1.2.3 - 2026-08-18

### Fixed
- Improved error handling and type safety.
- Fixed the capacity of future dates not being taken into account.

### Security
- Updated dependencies.

## 1.2.2 - 2026-07-23

### Fixed
- Further performance improvements for the `get:list:*` commands.

## 1.2.1 - 2026-07-23

### Fixed
- **Greatly** improved the performance of the `get:list:*` commands by reducing the number of API calls drastically.
- Fixed a bug where sub-hour negative durations were incorrectly displayed as positive durations in tables.

## 1.2.0 - 2026-07-22

### Added
- Added the `get:total:this-month` command. Run `overtimely help get:total:this-month` for more information.
- Added the `get:total:this-week` command. Run `overtimely help get:total:this-week` for more information.

### Changed
- Changed multiple command signatures to establish a more consistent naming scheme:
  - `get:last-month` => `get:total:last-month`
  - `get:last-week` => `get:total:last-week`
  - `get:months` => `get:list:months`
  - `get:weeks` => `get:list:weeks`
- Changed multiple command descriptions to be more verbose.
- Restyled multiple command outputs.
- Added an alert banner to the `get:total` command's output when you are on overtime or have minus hours.

## 1.1.1 - 2026-07-22

### Fixed
- Fixed the unintended export-exclusion of the app build.

## 1.1.0 - 2026-07-22

### Added
- Added the `get:weeks` command working analog to the `get:months` command, but with weeks instead of months as intervals.
- Added `-s` shorthand for the `--since` option of the `get:*` commands.
- Added `-u` shorthand for the `--until` option of the `get:*` commands.

### Changed
- Changed command outputs (mostly wording) in multiple places.
- Cleaned up the autoloader.

### Removed
- Removed unneeded files from the package installation.

### Fixed
- Improved period parsing and related error handling in the `get:*` commands.
- Improved date comparison safety.

## 1.0.0 - 2026-07-20

### Added
- Initial release
