<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentDeveloperLogin\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OutatimeIo\FilamentDeveloperLogin\DeveloperLoginPlugin;

final class Audit
{
    public function dispatch(object $event, DeveloperLoginPlugin $plugin): void
    {
        event($event);
        if ($channel = config('filament-developer-login.audit.log_channel')) {
            Log::channel($channel)->info('filament-developer-login', ['event' => $event::class, 'reason' => $event->reason ?? 'succeeded', 'panel' => $event->panelId]);
        }
    }

    public function ip(DeveloperLoginPlugin $plugin, Request $request): ?string
    {
        if (! $plugin->shouldLogIps()) {
            return null;
        }
        $ip = $request->ip();

        return $plugin->ipTransformer() ? app()->call($plugin->ipTransformer(), ['ip' => $ip]) : $ip;
    }
}
