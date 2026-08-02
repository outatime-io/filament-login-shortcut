<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Tests\Fixtures;

use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['can_access_panel' => 'boolean'];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can_access_panel;
    }
}
