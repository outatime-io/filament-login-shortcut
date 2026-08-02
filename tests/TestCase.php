<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentDeveloperLogin\Tests;

use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use OutatimeIo\FilamentDeveloperLogin\FilamentDeveloperLoginServiceProvider;
use OutatimeIo\FilamentDeveloperLogin\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SupportServiceProvider::class,
            FormsServiceProvider::class,
            FilamentServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentDeveloperLoginServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('filament-developer-login.user_model', User::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->boolean('can_access_panel')->default(true);
        });

        $this->app['session.store']->start();
        $this->app['request']->setLaravelSession($this->app['session.store']);
        $this->app->rebinding('request', function ($app, $request): void {
            $request->setLaravelSession($app['session.store']);
        });
    }
}
