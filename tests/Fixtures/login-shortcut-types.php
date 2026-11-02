<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Tests\TypeInference;

use Illuminate\Database\Eloquent\Builder;
use OutatimeIo\FilamentLoginShortcut\LoginShortcutPlugin;
use OutatimeIo\FilamentLoginShortcut\Tests\Fixtures\ScopedUser;

use function PHPStan\Testing\assertType;

function configuredModelCallbacks(): void
{
    LoginShortcutPlugin::make()
        ->userModel(ScopedUser::class)
        ->enabled()
        ->usersUsingQuery(function (Builder $query): Builder {
            assertType('Illuminate\Database\Eloquent\Builder<OutatimeIo\FilamentLoginShortcut\Tests\Fixtures\ScopedUser>', $query);
            assertType('Illuminate\Database\Eloquent\Builder<OutatimeIo\FilamentLoginShortcut\Tests\Fixtures\ScopedUser>', $query->canLogin());

            return $query->canLogin();
        });

    $plugin = LoginShortcutPlugin::make();
    $plugin->userModel(ScopedUser::class);
    $plugin->usersUsingQuery(function (Builder $query): Builder {
        assertType('Illuminate\Database\Eloquent\Builder<OutatimeIo\FilamentLoginShortcut\Tests\Fixtures\ScopedUser>', $query);

        return $query->canLogin();
    });
}
