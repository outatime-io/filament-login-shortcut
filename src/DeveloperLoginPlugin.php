<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentDeveloperLogin;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OutatimeIo\FilamentDeveloperLogin\Exceptions\InvalidConfiguration;

final class DeveloperLoginPlugin implements Plugin
{
    private bool|Closure|null $enabled = null;

    /** @var list<string>|null */
    private ?array $environments = null;

    private ?string $model = null;

    /** @var list<string>|null */
    private ?array $columns = null;

    private ?int $limit = null;

    private ?int $minimumLength = null;

    private ?int $debounce = null;

    private ?Closure $label = null;

    private ?Closure $authorize = null;

    private ?Closure $query = null;

    /** @var list<string>|null */
    private ?array $emails = null;

    /** @var list<string>|null */
    private ?array $domains = null;

    private ?bool $logIps = null;

    private ?Closure $transformIp = null;

    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'filament-developer-login';
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, fn (): View => $this->hookView($panel));
    }

    public function boot(Panel $panel): void {}

    public function enabled(bool|Closure $enabled = true): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /** @param list<string> $environments */
    public function allowedEnvironments(array $environments): self
    {
        $this->environments = $this->normalize($environments);

        return $this;
    }

    /** @param class-string<Model&Authenticatable> $model */
    public function userModel(string $model): self
    {
        $this->assertModel($model);
        $this->model = $model;

        return $this;
    }

    /** @param list<string> $columns */
    public function searchColumns(array $columns): self
    {
        $this->columns = $this->validateColumns($columns);

        return $this;
    }

    public function searchResultLimit(int $limit): self
    {
        if ($limit < 1) {
            throw new InvalidConfiguration('The result limit must be positive.');
        } $this->limit = $limit;

        return $this;
    }

    public function minimumSearchLength(int $length): self
    {
        if ($length < 1) {
            throw new InvalidConfiguration('The minimum search length must be positive.');
        } $this->minimumLength = $length;

        return $this;
    }

    public function searchDebounce(int $milliseconds): self
    {
        if ($milliseconds < 0) {
            throw new InvalidConfiguration('The debounce must not be negative.');
        } $this->debounce = $milliseconds;

        return $this;
    }

    public function userLabelUsing(Closure $callback): self
    {
        $this->label = $callback;

        return $this;
    }

    public function authorizeUsing(Closure $callback): self
    {
        $this->authorize = $callback;

        return $this;
    }

    /**
     * @param  Closure(Builder<Model>): Builder<Model>  $callback
     */
    public function usersUsingQuery(Closure $callback): self
    {
        $this->strategy();
        $this->query = $callback;

        return $this;
    }

    public function allUsers(): self
    {
        $this->strategy();

        return $this;
    }

    /** @param list<string> $emails */
    public function usersWithEmails(array $emails): self
    {
        $this->strategy();
        $this->emails = array_values(array_unique(array_filter(array_map(fn ($email): string => mb_strtolower(trim((string) $email)), $emails))));

        return $this;
    }

    /** @param list<string> $domains */
    public function usersWithEmailDomains(array $domains): self
    {
        $this->strategy();
        $this->domains = array_values(array_unique(array_filter(array_map(function ($domain): string {
            $domain = mb_strtolower(ltrim(trim((string) $domain), '@'));
            if ($domain === '' || str_contains($domain, '@') || str_contains($domain, '/') || str_contains($domain, '\\')) {
                throw new InvalidConfiguration('Invalid email domain.');
            }

            return $domain;
        }, $domains))));

        return $this;
    }

    public function logIpAddresses(bool $value = true): self
    {
        $this->logIps = $value;

        return $this;
    }

    public function transformIpAddressUsing(Closure $callback): self
    {
        $this->transformIp = $callback;

        return $this;
    }

    public function isEnabled(): bool|Closure
    {
        return $this->enabled ?? false;
    }

    /** @return list<string> */
    public function environments(): array
    {
        return $this->environments ?? ['local'];
    }

    /** @return class-string<Model&Authenticatable> */
    public function model(): string
    {
        $model = $this->model ?? (string) config('filament-developer-login.user_model');
        $this->assertModel($model);

        return $model;
    }

    /** @return list<string> */
    public function columns(): array
    {
        return $this->columns ?? $this->validateColumns((array) config('filament-developer-login.search.columns', ['email']));
    }

    public function limit(): int
    {
        return min($this->limit ?? (int) config('filament-developer-login.search.result_limit', 20), (int) config('filament-developer-login.search.hard_maximum', 100));
    }

    public function minimumLength(): int
    {
        return $this->minimumLength ?? (int) config('filament-developer-login.search.minimum_length', 1);
    }

    public function debounce(): int
    {
        return $this->debounce ?? (int) config('filament-developer-login.search.debounce', 300);
    }

    public function label(): ?Closure
    {
        return $this->label;
    }

    public function authorization(): ?Closure
    {
        return $this->authorize;
    }

    public function query(): ?Closure
    {
        return $this->query;
    }

    /** @return list<string>|null */
    public function emails(): ?array
    {
        return $this->emails;
    }

    /** @return list<string>|null */
    public function domains(): ?array
    {
        return $this->domains;
    }

    public function shouldLogIps(): bool
    {
        return $this->logIps ?? (bool) config('filament-developer-login.audit.log_ip_addresses', false);
    }

    public function ipTransformer(): ?Closure
    {
        return $this->transformIp;
    }

    private function strategy(): void
    {
        $this->query = null;
        $this->emails = null;
        $this->domains = null;
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function normalize(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($value): string => mb_strtolower(trim((string) $value)), $values))));
    }

    /**
     * @param  list<mixed>  $columns
     * @return list<string>
     */
    private function validateColumns(array $columns): array
    {
        $columns = array_values(array_unique(array_filter(array_map('strval', $columns))));
        foreach ($columns as $column) {
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
                throw new InvalidConfiguration('Search columns must be simple model columns.');
            }
        } if ($columns === []) {
            throw new InvalidConfiguration('At least one search column is required.');
        }

        return $columns;
    }

    private function hookView(Panel $panel): View
    {
        return view()->file(__DIR__.'/../resources/views/livewire/hook.blade.php', ['panelId' => $panel->getId()]);
    }

    private function assertModel(string $model): void
    {
        if (! class_exists($model) || ! is_subclass_of($model, Model::class) || ! is_subclass_of($model, Authenticatable::class)) {
            throw new InvalidConfiguration('The user model must extend Eloquent Model and implement Authenticatable.');
        }
    }
}
