<?php

declare(strict_types=1);

use Filament\Panel;
use Illuminate\Http\Request;
use OutatimeIo\FilamentLoginShortcut\LoginShortcutPlugin;
use OutatimeIo\FilamentLoginShortcut\Support\Availability;

it('allows an explicitly enabled local environment even when it is omitted from the allow-list', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $plugin = LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging']);

    expect(app(Availability::class)->allows($plugin, Panel::make(), Request::create('/')))->toBeTrue();
});

it('denies an explicitly enabled non-local allow-listed environment without an authorization callback', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');

    $plugin = LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging']);

    expect(app(Availability::class)->allows($plugin, Panel::make(), Request::create('/')))->toBeFalse();
});
