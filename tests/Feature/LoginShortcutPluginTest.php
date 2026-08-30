<?php

declare(strict_types=1);

use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use OutatimeIo\FilamentLoginShortcut\Exceptions\InvalidConfiguration;
use OutatimeIo\FilamentLoginShortcut\LoginShortcutPlugin;

it('requires panel-provider enablement and does not read availability configuration', function (): void {
    config()->set('filament-login-shortcut.enabled', true);
    config()->set('filament-login-shortcut.allowed_environments', ['staging']);

    expect(LoginShortcutPlugin::make()->isEnabled())->toBeFalse()
        ->and(LoginShortcutPlugin::make()->environments())->toBe(['local']);
});

it('normalizes and replaces user strategies', function (): void {
    $plugin = LoginShortcutPlugin::make()
        ->usersWithEmails([' ADMIN@example.test ', 'admin@example.test'])
        ->usersWithEmailDomains(['@local.test']);

    expect($plugin->emails())->toBeNull()
        ->and($plugin->domains())->toBe(['local.test']);
});

it('rejects unsafe search columns and invalid domains', function (): void {
    expect(fn () => LoginShortcutPlugin::make()->searchColumns(['email; drop table users']))->toThrow(InvalidConfiguration::class)
        ->and(fn () => LoginShortcutPlugin::make()->usersWithEmailDomains(['user@example.test']))->toThrow(InvalidConfiguration::class);
});

it('caps configured result limits', function (): void {
    config()->set('filament-login-shortcut.search.hard_maximum', 50);
    expect(LoginShortcutPlugin::make()->searchResultLimit(500)->limit())->toBe(50);
});

it('treats an explicitly empty domain list as an empty strategy', function (): void {
    expect(LoginShortcutPlugin::make()->usersWithEmailDomains([])->domains())->toBe([]);
});

it('normalizes, deduplicates, and exposes the built-in IP allow-list', function (): void {
    expect(LoginShortcutPlugin::make()->ipAllowlist())->toBeNull()
        ->and(LoginShortcutPlugin::make()->allowedIps(['10.0.0.5', ' 2001:DB8::7 ', '2001:db8:0:0:0:0:0:7'])->ipAllowlist())->toBe(['10.0.0.5', '2001:db8::7']);
});

it('rejects empty and malformed allowed IP entries', function (): void {
    expect(fn () => LoginShortcutPlugin::make()->allowedIps(['10.0.0.5', '']))->toThrow(InvalidConfiguration::class)
        ->and(fn () => LoginShortcutPlugin::make()->allowedIps(['not-an-ip']))->toThrow(InvalidConfiguration::class);
});

it('resolves closure-based IP allow-lists and fails closed when resolution errors', function (): void {
    $handler = Mockery::mock(ExceptionHandler::class);
    $handler->shouldReceive('report')->times(3);
    app()->instance(ExceptionHandler::class, $handler);

    expect(LoginShortcutPlugin::make()->allowedIps(fn (): array => ['10.0.0.5'])->ipAllowlist())->toBe(['10.0.0.5'])
        ->and(LoginShortcutPlugin::make()->allowedIps(fn (): array => throw new RuntimeException)->ipAllowlist())->toBe([])
        ->and(LoginShortcutPlugin::make()->allowedIps(fn (): array => ['not-an-ip'])->ipAllowlist())->toBe([])
        ->and(LoginShortcutPlugin::make()->allowedIps(fn (): string => '10.0.0.5')->ipAllowlist())->toBe([]);
});

it('uses a MariaDB-safe backslash escape literal for user searches', function (): void {
    $query = app(\OutatimeIo\FilamentLoginShortcut\Query\EligibleUsers::class)
        ->matching(LoginShortcutPlugin::make()->searchColumns(['email']), 'user')
        ->toRawSql();

    expect($query)->toContain('ESCAPE CHAR(92)');
});
