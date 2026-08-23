<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Support;

use Filament\Panel;
use Illuminate\Http\Request;
use OutatimeIo\FilamentLoginShortcut\LoginShortcutPlugin;

final class Availability
{
    public function allows(LoginShortcutPlugin $plugin, Panel $panel, Request $request): bool
    {
        $environment = mb_strtolower((string) app()->environment());
        $environmentIsAllowed = $environment === 'local' || in_array($environment, $plugin->environments(), true);
        if (! $this->evaluate($plugin->isEnabled(), $request, $panel, $environment) || ! $environmentIsAllowed) {
            return false;
        }

        if ($environment === 'local') {
            return $plugin->authorization() === null || $this->evaluate($plugin->authorization(), $request, $panel, $environment);
        }

        $allowlist = $plugin->ipAllowlist();
        $clientIp = IpAddress::canonical($request->ip());

        if ($allowlist !== null && ($clientIp === null || ! in_array($clientIp, $allowlist, true))) {
            return false;
        }

        if ($allowlist === null && $plugin->authorization() === null) {
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
