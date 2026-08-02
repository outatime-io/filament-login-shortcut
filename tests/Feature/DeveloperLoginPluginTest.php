<?php

declare(strict_types=1);

use OutatimeIo\FilamentDeveloperLogin\DeveloperLoginPlugin;
use OutatimeIo\FilamentDeveloperLogin\Exceptions\InvalidConfiguration;

it('requires panel-provider enablement and does not read availability configuration', function (): void {
    config()->set('filament-developer-login.enabled', true);
    config()->set('filament-developer-login.allowed_environments', ['staging']);

    expect(DeveloperLoginPlugin::make()->isEnabled())->toBeFalse()
        ->and(DeveloperLoginPlugin::make()->environments())->toBe(['local']);
});

it('normalizes and replaces user strategies', function (): void {
    $plugin = DeveloperLoginPlugin::make()
        ->usersWithEmails([' ADMIN@example.test ', 'admin@example.test'])
        ->usersWithEmailDomains(['@local.test']);

    expect($plugin->emails())->toBeNull()
        ->and($plugin->domains())->toBe(['local.test']);
});

it('rejects unsafe search columns and invalid domains', function (): void {
    expect(fn () => DeveloperLoginPlugin::make()->searchColumns(['email; drop table users']))->toThrow(InvalidConfiguration::class)
        ->and(fn () => DeveloperLoginPlugin::make()->usersWithEmailDomains(['user@example.test']))->toThrow(InvalidConfiguration::class);
});

it('caps configured result limits', function (): void {
    config()->set('filament-developer-login.search.hard_maximum', 50);
    expect(DeveloperLoginPlugin::make()->searchResultLimit(500)->limit())->toBe(50);
});

it('treats an explicitly empty domain list as an empty strategy', function (): void {
    expect(DeveloperLoginPlugin::make()->usersWithEmailDomains([])->domains())->toBe([]);
});
