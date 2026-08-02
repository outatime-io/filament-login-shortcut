<?php

declare(strict_types=1);

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
