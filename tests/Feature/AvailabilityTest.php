<?php

declare(strict_types=1);

use Closure;
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

function availabilityRequest(string $ip): Request
{
    return Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $ip]);
}

it('allows an allow-listed client IP outside local without an authorization callback', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');

    $plugin = LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging'])
        ->allowedIps(['10.0.0.5']);

    expect(app(Availability::class)->allows($plugin, Panel::make(), availabilityRequest('10.0.0.5')))->toBeTrue();
});

it('denies a client IP that is missing from the allow-list outside local', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');

    $plugin = LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging'])
        ->allowedIps(['10.0.0.5']);

    expect(app(Availability::class)->allows($plugin, Panel::make(), availabilityRequest('192.0.2.99')))->toBeFalse();
});

it('requires both the IP allow-list and the authorization callback to pass outside local', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');
    $availability = app(Availability::class);

    $plugin = fn (Closure $callback): LoginShortcutPlugin => LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging'])
        ->allowedIps(['10.0.0.5'])
        ->authorizeUsing($callback);

    expect($availability->allows($plugin(fn (): bool => true), Panel::make(), availabilityRequest('10.0.0.5')))->toBeTrue()
        ->and($availability->allows($plugin(fn (): bool => false), Panel::make(), availabilityRequest('10.0.0.5')))->toBeFalse()
        ->and($availability->allows($plugin(fn (): bool => true), Panel::make(), availabilityRequest('192.0.2.99')))->toBeFalse()
        ->and($availability->allows($plugin(fn (): bool => false), Panel::make(), availabilityRequest('192.0.2.99')))->toBeFalse();
});

it('does not apply the IP allow-list in the local environment', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $plugin = LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging'])
        ->allowedIps(['10.0.0.5']);

    expect(app(Availability::class)->allows($plugin, Panel::make(), availabilityRequest('192.0.2.99')))->toBeTrue();
});

it('fails closed when a closure-based IP allow-list cannot be resolved', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');

    $plugin = LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging'])
        ->allowedIps(fn (): array => throw new RuntimeException);

    expect(app(Availability::class)->allows($plugin, Panel::make(), availabilityRequest('10.0.0.5')))->toBeFalse();
});

it('denies every non-local client when the allow-list is explicitly empty', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');

    $plugin = LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging'])
        ->allowedIps([]);

    expect(app(Availability::class)->allows($plugin, Panel::make(), availabilityRequest('10.0.0.5')))->toBeFalse();
});

it('matches canonically equivalent IPv6 spellings outside local', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');

    $plugin = LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging'])
        ->allowedIps(['2001:0DB8:0000:0000:0000:0000:0000:0007']);

    expect(app(Availability::class)->allows($plugin, Panel::make(), availabilityRequest('2001:db8::7')))->toBeTrue();
});

it('fails closed when a closure-based IP allow-list resolves to malformed entries', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');

    $plugin = LoginShortcutPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['staging'])
        ->allowedIps(fn (): array => ['not-an-ip']);

    expect(app(Availability::class)->allows($plugin, Panel::make(), availabilityRequest('10.0.0.5')))->toBeFalse();
});
