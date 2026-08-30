<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Support;

final class IpAddress
{
    public static function canonical(mixed $ip): ?string
    {
        $ip = trim((string) $ip);

        if ($ip === '') {
            return null;
        }

        $packed = @inet_pton($ip);

        return $packed === false ? null : (string) @inet_ntop($packed);
    }
}
