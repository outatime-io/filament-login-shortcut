# Security policy

## Supported versions

Security fixes target the latest release line of this package. Please update to the most recent release before reporting an issue.

## Reporting a vulnerability

Please report suspected vulnerabilities **privately** through GitHub's private vulnerability reporting for this repository (the repository's *Security* tab). Do not open public GitHub issues for security reports.

Include reproduction steps, an impact assessment, affected versions, and any proposed mitigation. Maintainers will acknowledge reports and coordinate a fix and disclosure timeline with you.

## Scope

This package intentionally provides a passwordless sign-in shortcut for Filament panels. It is disabled unless explicitly enabled per panel, and outside the `local` environment it additionally requires an application-defined authorization callback. Reports in the following areas are especially welcome:

- Rendering or using the shortcut when the plugin has not been enabled
- Circumventing the environment allow-list or the authorization callback
- Logging in as a user who should have been excluded by the configured eligibility strategy or by `canAccessPanel()`
- Session fixation, rate-limit evasion, or redirects away from the application host

The authorization callback is application code: its correctness (for example, IP allow-listing behind trusted proxies) is the deploying application's responsibility.
