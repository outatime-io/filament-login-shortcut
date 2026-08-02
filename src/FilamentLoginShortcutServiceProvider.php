<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use OutatimeIo\FilamentLoginShortcut\Livewire\LoginShortcut;

final class FilamentLoginShortcutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-login-shortcut.php', 'filament-login-shortcut');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/filament-login-shortcut.php' => config_path('filament-login-shortcut.php')], 'filament-login-shortcut-config');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-login-shortcut');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-login-shortcut');
        Livewire::component('filament-login-shortcut', LoginShortcut::class);
    }
}
