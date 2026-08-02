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
use Livewire\Component;
use OutatimeIo\FilamentDeveloperLogin\DeveloperLoginPlugin;
use OutatimeIo\FilamentDeveloperLogin\Query\EligibleUsers;
use OutatimeIo\FilamentDeveloperLogin\Support\Availability;

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

        $this->validate(['data.selectedIdentifier' => ['required', 'string', 'max:255']]);

        $guard = auth()->guard($this->panel()->getAuthGuard());

        if ($guard->check()) {
            return null;
        }

        $model = $plugin->model();

        $identifierName = (new $model)->getAuthIdentifierName();

        /** @var (Model&Authenticatable)|null $user */
        $user = app(EligibleUsers::class)->build($plugin)->where($identifierName, $this->data['selectedIdentifier'])->first();

        if (! $user instanceof Authenticatable) {
            return null;
        }

        if (method_exists($user, 'canAccessPanel') && ! $user->canAccessPanel($this->panel())) {
            return null;
        }

        $guard->login($user);
        request()->session()->regenerate();

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
        if (mb_strlen(trim($search)) < $plugin->minimumLength()) {
            return [];
        }

        /** @var Collection<int, Model&Authenticatable> $users */
        $users = app(EligibleUsers::class)->matching($plugin, trim($search))->limit($plugin->limit())->get();
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

    private function safeDestination(): string
    {
        $fallback = $this->panel()->getUrl() ?? '/';
        $intended = (string) session()->pull('url.intended', '');
        $host = parse_url($intended, PHP_URL_HOST);

        return $intended !== '' && ($host === null || $host === request()->getHost()) ? $intended : $fallback;
    }
}
