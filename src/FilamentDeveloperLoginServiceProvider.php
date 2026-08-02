<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentDeveloperLogin;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use OutatimeIo\FilamentDeveloperLogin\Livewire\DeveloperLogin;

final class FilamentDeveloperLoginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-developer-login.php', 'filament-developer-login');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/filament-developer-login.php' => config_path('filament-developer-login.php')], 'filament-developer-login-config');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-developer-login');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-developer-login');
        Livewire::component('filament-developer-login', DeveloperLogin::class);
    }
}
