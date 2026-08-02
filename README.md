# Filament Login Shortcut

A secure login shortcut selector for Filament panels.

> **Security warning:** This package provides a passwordless sign-in shortcut. Enabling it outside a local environment can grant access to privileged user accounts. Non-local use requires an application-defined authorization callback and appropriate network and organizational controls.

Production use should generally remain disabled, even though deliberate production activation is technically possible.

## Compatibility

| Package | Supported versions |
| --- | --- |
| PHP | 8.2–8.4 |
| Laravel | 12–13 (as resolved by Filament) |
| Livewire | 3.x with Filament 4; 4.x with Filament 5 |
| Filament | 4.x and 5.x |

Filament v3 is intentionally unsupported. CI exercises representative lowest/current Laravel and PHP combinations for both Filament majors.

## Installation

```bash
composer require --dev outatime-io/filament-login-shortcut
php artisan vendor:publish --tag=filament-login-shortcut-config
```

Install this package as a development dependency by default. Deploy production and staging with `composer install --no-dev --optimize-autoloader`; this keeps the passwordless login code out of those deployments entirely.

Use a normal dependency only when you intentionally need the login shortcut in a non-local environment, such as a protected staging system. In that case, configure an explicit non-local authorization callback and ensure the package is installed in that environment.

Register it independently on every intended panel:

```php
use OutatimeIo\FilamentLoginShortcut\LoginShortcutPlugin;

$panel->plugin(
    LoginShortcutPlugin::make()
        ->enabled(),
);
```

The plugin uses Filament's documented `AUTH_LOGIN_FORM_BEFORE` hook, so it appears before the normal form without replacing it. A shared Livewire component provides debounced, bounded search. The v4/v5 integration is deliberately limited to this common public render-hook and panel-guard API; all availability, query, and login logic is version-neutral.

## Safe defaults and environments

The feature is off by default and can only be enabled in the panel provider. `FILAMENT_LOGIN_SHORTCUT_ENABLED` and `FILAMENT_LOGIN_SHORTCUT_ALLOWED_ENVIRONMENTS` are not supported. Local is always allowed once explicitly enabled, so no environment configuration is needed for a local-only setup:

```php
LoginShortcutPlugin::make()
    ->enabled();
```

To allow a non-local environment, include its exact name in the panel-provider allow-list. An authorization callback is mandatory outside `local` and is re-evaluated for render, each search, and submit:

```php
use Filament\Panel;
use Illuminate\Http\Request;

LoginShortcutPlugin::make()
    ->enabled(true)
    ->allowedEnvironments(['local', 'staging'])
    ->authorizeUsing(
        fn (Request $request, Panel $panel, string $environment): bool => $environment === 'local'
            || in_array($request->ip(), config('services.login_shortcut.allowed_ips', []), true),
    );
```

Good policies include a VPN/private-network check, an application access policy, or an identity-aware proxy assertion. Do not trust `X-Forwarded-For` or similar headers unless Laravel trusted proxies are configured correctly. Exceptions fail closed. A translated warning is displayed whenever an authorized non-local component renders.

## Plugin API reference

All fluent methods return the plugin instance, so they can be chained in a panel provider. The configuration methods below are the supported public API; the other public methods on the class are internal accessors used by the package.

| Method | Argument | Default | Purpose |
| --- | --- | --- | --- |
| `make()` | none | — | Creates a new plugin instance. |
| `enabled(bool\|Closure $enabled = true)` | Boolean or callback receiving `Request`, `Panel`, and environment | `false` | Turns the feature on or off. It must be enabled even on local. |
| `allowedEnvironments(array $environments)` | Exact environment names | `['local']` | Allows named non-local environments. Local is always allowed once enabled. |
| `userModel(string $model)` | Eloquent `Authenticatable` model class | `App\\Models\\User` | Changes the queried and authenticated model. Invalid model classes throw `InvalidConfiguration`. |
| `searchColumns(array $columns)` | Simple database column names | `['email']` | Defines searchable columns. An empty list or unsafe name throws `InvalidConfiguration`. |
| `searchResultLimit(int $limit)` | Positive integer | `20` | Caps initially shown and searched results; the hard maximum still applies. |
| `minimumSearchLength(int $length)` | Positive integer | `1` | Suppresses remote search below this character count. |
| `searchDebounce(int $milliseconds)` | Integer at least zero | `300` | Sets the search debounce interval in milliseconds. |
| `userLabelUsing(Closure $callback)` | `fn (Authenticatable $user): string` | User `email` | Produces the visible user label. |
| `authorizeUsing(Closure $callback)` | `fn (Request $request, Panel $panel, string $environment): bool` | none | Required outside local; use IP/VPN, policy, or proxy authorization. `false` or exceptions deny access. |
| `allUsers()` | none | active default strategy | Lists every configured-model user. It never filters the Select by `canAccessPanel()`. Replaces another strategy. |
| `usersWithEmails(array $emails)` | Exact email addresses | none | Restricts eligible users to these addresses. Replaces another strategy. |
| `usersWithEmailDomains(array $domains)` | Exact domains, optional `@` | none | Restricts eligible users to these domain suffixes. Replaces another strategy. |
| `usersUsingQuery(Closure $callback)` | `fn (Builder $query): Builder` for configured model | none | Provides a custom eligible-users query. Replaces another strategy; a wrong builder/model throws `InvalidConfiguration`. |
| `logIpAddresses(bool $value = true)` | Boolean | config `audit.log_ip_addresses` (`false`) | Includes IPs in audit records. Enable only with suitable retention controls. |
| `transformIpAddressUsing(Closure $callback)` | `fn (?string $ip): ?string` | none | Transforms an IP before it is included in audit data, e.g. hashes it. |

### Filtering selectable users

The Select is filtered **only** by the active eligibility strategy:

- `allUsers()` — the default — lists every user from the configured model.
- `usersWithEmails()` and `usersWithEmailDomains()` list only the matching users.
- `usersUsingQuery()` lists only the users returned by its Eloquent query.

> **Important:** `canAccessPanel()` does **not** filter the initially displayed users or search results. Changing a user's `canAccessPanel()` result alone will never add or remove that user from the Select.

The package calls `canAccessPanel()` only after a user has been selected and the **Login as user** button is pressed. If it returns `false`, login is denied; the user may still have appeared in the Select.

To filter the Select, configure `usersUsingQuery()` in the panel provider. The callback becomes the database query for the initial options and search results. For example, CourtDesk's admin panel permits only users with `is_admin = true`:

```php
use Illuminate\Database\Eloquent\Builder;

LoginShortcutPlugin::make()
    ->enabled()
    ->usersUsingQuery(
        fn (Builder $query): Builder => $query->where('is_admin', true),
    );
```

With this configuration, non-admin users are never queried for or shown in the Select. `canAccessPanel()` remains a separate final authorization check at login.

### Package configuration reference

Published configuration supplies fallbacks for the options below when a panel-provider method is not called. Enablement and allowed environments are configured only through the panel provider.

| Key | Default | Notes |
| --- | --- | --- |
| `user_model` | `App\\Models\\User` | Fallback user model. |
| `search.columns` | `['email']` | Fallback searchable columns. |
| `search.result_limit` | `20` | Fallback result limit. |
| `search.hard_maximum` | `100` | Absolute result-limit ceiling. |
| `search.minimum_length` | `1` | Fallback search threshold. |
| `search.debounce` | `300` | Fallback debounce in milliseconds. |
| `rate_limits.searches_per_minute` | `60` | Per panel/session/IP search limit. |
| `rate_limits.logins_per_minute` | `10` | Per panel/session/IP login limit. |
| `rate_limits.denials_per_minute` | `20` | Per panel/session/IP denial limit. |
| `audit.log_channel` | `null` | Optional Laravel log channel for concise audit records. |
| `audit.log_ip_addresses` | `false` | Whether audit entries include IP data. |

### Integration accessors

These public methods exist for Filament and the package's Livewire/query services. Application code normally configures the methods above instead of calling these directly.

| Method | Returns | Purpose |
| --- | --- | --- |
| `getId()` | `string` | The fixed plugin identifier: `filament-login-shortcut`. |
| `register(Panel $panel)` | `void` | Registers the login-form render hook. Called by Filament. |
| `boot(Panel $panel)` | `void` | Filament lifecycle hook; currently no operation. |
| `isEnabled()` | `bool\|Closure` | Resolves the panel-provider enabled value. |
| `environments()` | `array` | Resolves normalized allowed environments. |
| `model()` | `string` | Resolves and validates the configured user model. |
| `columns()` | `array` | Resolves and validates search columns. |
| `limit()` | `int` | Resolves the result limit, capped by `search.hard_maximum`. |
| `minimumLength()` | `int` | Resolves the search threshold. |
| `debounce()` | `int` | Resolves the debounce interval. |
| `label()` | `?Closure` | Returns the optional user-label callback. |
| `authorization()` | `?Closure` | Returns the optional authorization callback. |
| `query()` | `?Closure` | Returns the custom-query strategy callback, if set. |
| `emails()` | `?array` | Returns the exact-email strategy list, if set. |
| `domains()` | `?array` | Returns the domain strategy list, if set. |
| `shouldLogIps()` | `bool` | Resolves whether audit entries include IP data. |
| `ipTransformer()` | `?Closure` | Returns the optional IP-transform callback. |

## Users, filters, and search

The default model is `App\Models\User`, label is `email`, search column is `email`, limit is 20, minimum length is 1, and debounce is 300ms. The model must be an Eloquent model implementing `Authenticatable`; integer, UUID, ULID, and other string auth identifiers are supported.

Strategies are mutually exclusive: the last strategy method called replaces the previous one. There is deliberately no ID-list strategy.

```php
// All users (default)
LoginShortcutPlugin::make()->enabled(true)->allUsers();

// Exact email addresses
LoginShortcutPlugin::make()->enabled(true)->usersWithEmails([
    'admin@example.test',
    'editor@example.test',
]);

// Exact email domains; @ is optional
LoginShortcutPlugin::make()->enabled(true)->usersWithEmailDomains(['local.test']);

// Custom query must return a Builder for the configured user model
use Illuminate\Database\Eloquent\Builder;
LoginShortcutPlugin::make()->enabled(true)->usersUsingQuery(
    fn (Builder $query): Builder => $query->where('is_admin', true),
);
```

Domain matching is parameterized and suffix-based (`person@example.test` matches, `person@notexample.test` and `person@example.test.attacker.test` do not). It lowercases both sides; database collation and Unicode case folding may still differ by engine.

```php
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

LoginShortcutPlugin::make()
    ->userModel(User::class)
    ->searchColumns(['email', 'name'])
    ->searchResultLimit(20)
    ->minimumSearchLength(1)
    ->searchDebounce(300)
    ->userLabelUsing(
        fn (Authenticatable $user): string => sprintf('%s (%s)', $user->getAttribute('name'), $user->getAttribute('email')),
    );
```

Search never runs below the minimum length, escapes SQL wildcard input, limits results with a hard maximum, and returns only identifier and label. At submission, the selected identifier is looked up again through the trusted constrained query.

## Authentication and auditing

The component refuses authenticated panel guards, uses the current panel's configured guard, and checks `canAccessPanel()` when present after a user is selected. It regenerates the session and redirects only to a same-host intended URL or the panel URL. It has separate per-panel/session/IP rate limits for search and login. It does not replace an existing session or act as impersonation.

Events are `AutoLoginSucceeded`, `AutoLoginDenied`, and `AutoLoginFailed`. They contain identifiers (not email/name), panel, environment, timestamp, and a stable reason code where relevant. IP addresses are absent by default:

```php
LoginShortcutPlugin::make()
    ->logIpAddresses(false)
    ->transformIpAddressUsing(fn (?string $ip): ?string => $ip ? hash('sha256', $ip) : null);
```

Set `audit.log_channel` in package configuration for optional concise log records. No database audit table is created.

## Privacy

You remain responsible for lawful purpose, access controls, log security, retention, deletion, incident review, and any IP-address processing. This package follows data minimization but does not itself make an application GDPR compliant.

## Testing, contributing, and releases

Run `composer validate`, `composer format`, `composer analyse`, and `composer test`. The GitHub workflow covers Filament 4 and 5; it excludes Filament 3. See [CONTRIBUTING.md](CONTRIBUTING.md), [SECURITY.md](SECURITY.md), [CHANGELOG.md](CHANGELOG.md), and [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md).

Before publication, configure the final GitHub/Packagist metadata and security contact.

## License

MIT. See [LICENSE.md](LICENSE.md).
