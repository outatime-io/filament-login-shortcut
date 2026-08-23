# Contributing

Thank you for considering a contribution. Please discuss security-sensitive changes before opening a pull request; suspected vulnerabilities must be reported privately as described in [SECURITY.md](SECURITY.md), never in public issues.

## Development environment

Requirements: PHP 8.2 or newer and Composer. The package is tested with [Orchestra Testbench](https://github.com/orchestra/testbench) against Laravel 12/13 and Filament 4/5, so no application skeleton is required:

```bash
composer install
```

## Scripts

| Command | Purpose |
| --- | --- |
| `composer test` | Runs the Pest test suite |
| `composer analyse` | Runs PHPStan static analysis |
| `composer format` | Checks code style with Pint (`--test` mode; run `vendor/bin/pint` to fix) |
| `composer check:composer` | Validates `composer.json` strictly |

CI runs all four commands on every push and pull request across the supported PHP/Laravel/Filament matrix.

## Pull requests

- Keep changes focused: one concern per pull request.
- Add behavioural Pest coverage for bug fixes and new features.
- Never weaken the enablement, authorization, or panel-access checks, and never add code that bypasses them.
- Run all scripts above locally before pushing.
- Use conventional commit messages (for example `docs:`, `feat:`, `fix:`).
