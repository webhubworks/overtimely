# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.1 - 2026-07-22

### Fixed
- Fixed unintended build export exclusion.

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
