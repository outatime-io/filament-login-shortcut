<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;

class ScopedUser extends User
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCanLogin(Builder $query): Builder
    {
        return $query->where('can_access_panel', true);
    }
}
