# Changelog

All notable changes will be documented in this file. This project follows Semantic Versioning.

## v1.1.0

- Added a login shortcut screenshot to the README to demonstrate the widget in action while keeping the security warning prominent.
- Added an informative rate-limit denial message that reports the remaining wait in seconds through a new pluralization-aware `rate_limited` translation key; other denials keep the generic unavailable message and audit events with reason codes are unchanged.
- Added `allowedIps()` for a built-in, exact-match client IP allow-list that satisfies the mandatory non-local authorization on its own and composes with `authorizeUsing()` under AND semantics. Entries are canonicalized so equivalent IPv6 spellings match; closure resolution errors are reported and fail closed.

## v1.0.1

- Added PHP 8.5 to the tested compatibility matrix and compatibility documentation.
- Allowed Pest 5 and pest-plugin-laravel 5 as dev dependencies.
- Normalized composer version constraint separators.
- Pinned GitHub Actions to full commit SHAs and installed the SQLite extensions explicitly in CI.
- Refined the Renovate update policy.

## v1.0.0

- Initial release of Filament Login Shortcut.
