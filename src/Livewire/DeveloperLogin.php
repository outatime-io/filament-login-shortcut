<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentDeveloperLogin\Livewire;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use OutatimeIo\FilamentDeveloperLogin\DeveloperLoginPlugin;
use OutatimeIo\FilamentDeveloperLogin\Events\AutoLoginDenied;
use OutatimeIo\FilamentDeveloperLogin\Events\AutoLoginFailed;
use OutatimeIo\FilamentDeveloperLogin\Events\AutoLoginSucceeded;
use OutatimeIo\FilamentDeveloperLogin\Query\EligibleUsers;
use OutatimeIo\FilamentDeveloperLogin\Support\Audit;
use OutatimeIo\FilamentDeveloperLogin\Support\Availability;
use OutatimeIo\FilamentDeveloperLogin\Support\Reason;

final class DeveloperLogin extends Component implements HasForms
{
    use InteractsWithForms;

    public string $panelId;

    /** @var array{selectedIdentifier?: string|null} */
    public array $data = [];

    public function mount(string $panelId): void
    {
        $this->panelId = $panelId;
        $this->guardAvailable();
        $this->getSchema('form')?->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedIdentifier')
                    ->hiddenLabel()
                    ->placeholder(__('filament-developer-login::messages.search_placeholder'))
                    ->searchable()
                    ->live()
                    ->native(false)
                    ->optionsLimit($this->plugin()->limit())
                    ->options(fn (): array => $this->initialUsers())
                    ->getSearchResultsUsing(fn (string $search): array => $this->searchUsers($search))
                    ->getOptionLabelUsing(fn (string $value): ?string => $this->selectedUserLabel($value)),
            ])
            ->statePath('data');
    }

    public function login(): mixed
    {
        if (! $this->guardAvailable()) {
            return null;
        }

        $plugin = $this->plugin();

        if (! $this->attempt('logins_per_minute', Reason::RATE_LIMITED)) {
            return null;
        }

        $this->validate(['data.selectedIdentifier' => ['required', 'string', 'max:255']]);

        $guard = auth()->guard($this->panel()->getAuthGuard());

        if ($guard->check()) {
            return $this->deny(Reason::AUTHENTICATED);
        }

        $model = $plugin->model();

        $identifierName = (new $model)->getAuthIdentifierName();

        /** @var (Model&Authenticatable)|null $user */
        $user = app(EligibleUsers::class)->build($plugin)->where($identifierName, $this->data['selectedIdentifier'])->first();

        if (! $user instanceof Authenticatable) {
            return $this->fail(Reason::INVALID_SELECTION);
        }

        if (method_exists($user, 'canAccessPanel') && ! $user->canAccessPanel($this->panel())) {
            return $this->fail(Reason::PANEL_ACCESS_DENIED);
        }

        $guard->login($user);
        request()->session()->regenerate();

        RateLimiter::hit($this->key('logins_per_minute'), 60);

        app(Audit::class)->dispatch(new AutoLoginSucceeded($user::class, (string) $user->getAuthIdentifier(), $this->panelId, (string) app()->environment(), new \DateTimeImmutable, app(Audit::class)->ip($plugin, request())), $plugin);

        return $this->redirect($this->safeDestination());
    }

    public function render(): View
    {
        return $this->componentView();
    }

    private function componentView(): View
    {
        return view()->file(dirname(__DIR__, 2).'/resources/views/livewire/developer-login.blade.php', [
            'available' => $this->guardAvailable(),
            'nonLocal' => app()->environment() !== 'local',
            'environments' => implode(', ', $this->plugin()->environments()),
        ]);
    }

    private function guardAvailable(): bool
    {
        $available = app(Availability::class)->allows($this->plugin(), $this->panel(), request());

        return $available && ! auth()->guard($this->panel()->getAuthGuard())->check();
    }

    private function plugin(): DeveloperLoginPlugin
    { /** @var DeveloperLoginPlugin $plugin */ $plugin = $this->panel()->getPlugin('filament-developer-login');

        return $plugin;
    }

    private function panel(): Panel
    {
        return Filament::getPanel($this->panelId);
    }

    private function label(Authenticatable $user, DeveloperLoginPlugin $plugin): string
    {
        return (string) ($plugin->label() ? app()->call($plugin->label(), ['user' => $user]) : $user->getAttribute('email'));
    }

    /** @return array<string, string> */
    private function searchUsers(string $search): array
    {
        if (! $this->guardAvailable()) {
            return [];
        }

        $plugin = $this->plugin();
        if (mb_strlen(trim($search)) < $plugin->minimumLength() || ! $this->attempt('searches_per_minute', Reason::RATE_LIMITED)) {
            return [];
        }

        /** @var Collection<int, Model&Authenticatable> $users */
        $users = app(EligibleUsers::class)->matching($plugin, trim($search))->limit($plugin->limit())->get();
        RateLimiter::hit($this->key('searches_per_minute'), 60);

        return $users->mapWithKeys(fn (Authenticatable $user): array => [(string) $user->getAuthIdentifier() => $this->label($user, $plugin)])->all();
    }

    /** @return array<string, string> */
    private function initialUsers(): array
    {
        if (! $this->guardAvailable()) {
            return [];
        }

        $plugin = $this->plugin();

        /** @var Collection<int, Model&Authenticatable> $users */
        $users = app(EligibleUsers::class)->build($plugin)->limit($plugin->limit())->get();

        return $users->mapWithKeys(fn (Authenticatable $user): array => [(string) $user->getAuthIdentifier() => $this->label($user, $plugin)])->all();
    }

    private function selectedUserLabel(string $identifier): ?string
    {
        if (! $this->guardAvailable()) {
            return null;
        }

        $model = $this->plugin()->model();

        /** @var (Model&Authenticatable)|null $user */
        $user = app(EligibleUsers::class)->build($this->plugin())->where((new $model)->getAuthIdentifierName(), $identifier)->first();

        return $user instanceof Authenticatable ? $this->label($user, $this->plugin()) : null;
    }

    private function attempt(string $limit, string $reason): bool
    {
        if (! RateLimiter::tooManyAttempts($this->key($limit), (int) config('filament-developer-login.rate_limits.'.$limit, 10))) {
            return true;
        } $this->deny($reason);

        return false;
    }

    private function key(string $operation): string
    {
        return 'filament-developer-login:'.$operation.':'.$this->panelId.':'.hash('sha256', (string) request()->session()->getId().'|'.(string) request()->ip());
    }

    private function deny(string $reason): null
    {
        app(Audit::class)->dispatch(new AutoLoginDenied($reason, $this->panelId, (string) app()->environment(), new \DateTimeImmutable, app(Audit::class)->ip($this->plugin(), request())), $this->plugin());
        throw ValidationException::withMessages(['selectedIdentifier' => __('filament-developer-login::messages.unavailable')]);
    }

    private function fail(string $reason): null
    {
        app(Audit::class)->dispatch(new AutoLoginFailed($reason, $this->panelId, (string) app()->environment(), new \DateTimeImmutable, app(Audit::class)->ip($this->plugin(), request())), $this->plugin());
        throw ValidationException::withMessages(['selectedIdentifier' => __('filament-developer-login::messages.login_failed')]);
    }

    private function safeDestination(): string
    {
        $fallback = $this->panel()->getUrl() ?? '/';
        $intended = (string) session()->pull('url.intended', '');
        $host = parse_url($intended, PHP_URL_HOST);

        return $intended !== '' && ($host === null || $host === request()->getHost()) ? $intended : $fallback;
    }
}
