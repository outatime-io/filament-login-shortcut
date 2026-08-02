<?php

declare(strict_types=1);

use Filament\Panel;
use Illuminate\Http\Request;
use OutatimeIo\FilamentDeveloperLogin\DeveloperLoginPlugin;
use OutatimeIo\FilamentDeveloperLogin\Support\Availability;

it('allows an explicitly enabled local environment even when it is omitted from the allow-list', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $plugin = DeveloperLoginPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging']);

    expect(app(Availability::class)->allows($plugin, Panel::make(), Request::create('/')))->toBeTrue();
});

it('denies an explicitly enabled non-local allow-listed environment without an authorization callback', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');

    $plugin = DeveloperLoginPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging']);

    expect(app(Availability::class)->allows($plugin, Panel::make(), Request::create('/')))->toBeFalse();
});
