<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OutatimeIo\FilamentLoginShortcut\LoginShortcutPlugin;

final class Audit
{
    public function dispatch(object $event, LoginShortcutPlugin $plugin): void
    {
        event($event);
        if ($channel = config('filament-login-shortcut.audit.log_channel')) {
            Log::channel($channel)->info('filament-login-shortcut', ['event' => $event::class, 'reason' => $event->reason ?? 'succeeded', 'panel' => $event->panelId]);
        }
    }

    public function ip(LoginShortcutPlugin $plugin, Request $request): ?string
    {
        if (! $plugin->shouldLogIps()) {
            return null;
        }
        $ip = $request->ip();

        return $plugin->ipTransformer() ? app()->call($plugin->ipTransformer(), ['ip' => $ip]) : $ip;
    }
}
