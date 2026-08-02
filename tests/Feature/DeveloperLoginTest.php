<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use OutatimeIo\FilamentDeveloperLogin\DeveloperLoginPlugin;
use OutatimeIo\FilamentDeveloperLogin\Events\AutoLoginDenied;
use OutatimeIo\FilamentDeveloperLogin\Events\AutoLoginFailed;
use OutatimeIo\FilamentDeveloperLogin\Events\AutoLoginSucceeded;
use OutatimeIo\FilamentDeveloperLogin\Livewire\DeveloperLogin;
use OutatimeIo\FilamentDeveloperLogin\Support\Reason;
use OutatimeIo\FilamentDeveloperLogin\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->plugin = DeveloperLoginPlugin::make()
        ->enabled(true)
        ->allowedEnvironments(['testing'])
        ->authorizeUsing(fn (): bool => true)
        ->allUsers()
        ->searchColumns(['email'])
        ->searchResultLimit(2);

    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->plugin($this->plugin);

    app(PanelRegistry::class)->register($panel);
    $panel->boot();
});

function developerLoginComponent(): Testable
{
    expect(Filament::getPanel('admin'))->toBeInstanceOf(Panel::class);

    return Livewire::test(DeveloperLogin::class, ['panelId' => 'admin']);
}

function searchResults(Testable $component, string $search): array
{
    return $component->instance()->getForm('form')->getComponent('selectedIdentifier')->getSearchResults($search);
}

function rateLimitKey(string $operation): string
{
    return 'filament-developer-login:'.$operation.':admin:'.hash('sha256', session()->getId().'|'.request()->ip());
}

it('searches eligible users case-insensitively, respects the result limit, and records the search attempt', function (): void {
    $alice = User::query()->create(['email' => 'alice@example.test']);
    User::query()->create(['email' => 'albert@example.test']);
    User::query()->create(['email' => 'other@example.test']);

    $results = searchResults(developerLoginComponent(), 'AL');

    expect($results)->toBe([
        (string) $alice->getAuthIdentifier() => 'alice@example.test',
        '2' => 'albert@example.test',
    ])->and(RateLimiter::attempts(rateLimitKey('searches_per_minute')))->toBe(1);
});

it('does not search when the term is shorter than the configured minimum length', function (): void {
    $this->plugin->minimumSearchLength(3);
    User::query()->create(['email' => 'alice@example.test']);

    expect(searchResults(developerLoginComponent(), 'al'))->toBe([])
        ->and(RateLimiter::attempts(rateLimitKey('searches_per_minute')))->toBe(0);
});

it('does not expose search options through an authenticated guard', function (): void {
    $user = User::query()->create(['email' => 'alice@example.test']);
    auth()->login($user);

    expect(searchResults(developerLoginComponent(), 'alice'))->toBe([]);
});

it('rate limits user searches and audits the denial', function (): void {
    Event::fake([AutoLoginDenied::class]);
    config()->set('filament-developer-login.rate_limits.searches_per_minute', 1);
    RateLimiter::hit(rateLimitKey('searches_per_minute'), 60);

    expect(fn (): array => searchResults(developerLoginComponent(), 'alice'))->toThrow(ValidationException::class);

    Event::assertDispatched(AutoLoginDenied::class, fn (AutoLoginDenied $event): bool => $event->reason === Reason::RATE_LIMITED && $event->panelId === 'admin');
});

it('logs in an eligible panel user, regenerates the session, redirects safely, and audits success', function (): void {
    Event::fake([AutoLoginSucceeded::class]);
    $user = User::query()->create(['email' => 'alice@example.test']);
    session()->put('url.intended', '/admin/dashboard');
    $previousSessionId = session()->getId();

    developerLoginComponent()
        ->set('data.selectedIdentifier', (string) $user->getAuthIdentifier())
        ->call('login')
        ->assertRedirect('/admin/dashboard');

    expect(auth()->id())->toBe($user->getAuthIdentifier())
        ->and(session()->getId())->not->toBe($previousSessionId)
        ->and(RateLimiter::attempts(rateLimitKey('logins_per_minute')))->toBe(1);
    Event::assertDispatched(AutoLoginSucceeded::class, fn (AutoLoginSucceeded $event): bool => $event->identifier === (string) $user->getAuthIdentifier() && $event->panelId === 'admin');
});

it('rejects a selected user that cannot access the panel and audits the failure', function (): void {
    Event::fake([AutoLoginFailed::class]);
    $user = User::query()->create(['email' => 'blocked@example.test', 'can_access_panel' => false]);

    developerLoginComponent()
        ->set('data.selectedIdentifier', (string) $user->getAuthIdentifier())
        ->call('login')
        ->assertHasErrors(['selectedIdentifier']);

    expect(auth()->check())->toBeFalse();
    Event::assertDispatched(AutoLoginFailed::class, fn (AutoLoginFailed $event): bool => $event->reason === Reason::PANEL_ACCESS_DENIED && $event->panelId === 'admin');
});

it('rejects an invalid selection and audits the failure without authenticating a user', function (): void {
    Event::fake([AutoLoginFailed::class]);

    developerLoginComponent()
        ->set('data.selectedIdentifier', 'missing')
        ->call('login')
        ->assertHasErrors(['selectedIdentifier']);

    expect(auth()->check())->toBeFalse();
    Event::assertDispatched(AutoLoginFailed::class, fn (AutoLoginFailed $event): bool => $event->reason === Reason::INVALID_SELECTION && $event->panelId === 'admin');
});

it('rate limits login attempts and audits the denial', function (): void {
    Event::fake([AutoLoginDenied::class]);
    config()->set('filament-developer-login.rate_limits.logins_per_minute', 1);
    RateLimiter::hit(rateLimitKey('logins_per_minute'), 60);

    developerLoginComponent()
        ->set('data.selectedIdentifier', '1')
        ->call('login')
        ->assertHasErrors(['selectedIdentifier']);

    expect(auth()->check())->toBeFalse();
    Event::assertDispatched(AutoLoginDenied::class, fn (AutoLoginDenied $event): bool => $event->reason === Reason::RATE_LIMITED && $event->panelId === 'admin');
});
