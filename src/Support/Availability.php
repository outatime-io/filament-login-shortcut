<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentDeveloperLogin\Support;

use Filament\Panel;
use Illuminate\Http\Request;
use OutatimeIo\FilamentDeveloperLogin\DeveloperLoginPlugin;

final class Availability
{
    public function allows(DeveloperLoginPlugin $plugin, Panel $panel, Request $request): bool
    {
        $environment = mb_strtolower((string) app()->environment());
        $environmentIsAllowed = $environment === 'local' || in_array($environment, $plugin->environments(), true);
        if (! $this->evaluate($plugin->isEnabled(), $request, $panel, $environment) || ! $environmentIsAllowed) {
            return false;
        }

        if ($environment !== 'local' && $plugin->authorization() === null) {
            return false;
        }

        return $plugin->authorization() === null || $this->evaluate($plugin->authorization(), $request, $panel, $environment);
    }

    private function evaluate(bool|\Closure $value, Request $request, Panel $panel, string $environment): bool
    {
        try {
            return (bool) ($value instanceof \Closure ? app()->call($value, ['request' => $request, 'panel' => $panel, 'environment' => $environment]) : $value);
        } catch (\Throwable) {
            return false;
        }
    }
}
