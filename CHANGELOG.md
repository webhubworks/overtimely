# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
