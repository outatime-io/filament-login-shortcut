<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Support;

final class Reason
{
    public const UNAVAILABLE = 'unavailable';

    public const UNAUTHORIZED = 'unauthorized';

    public const AUTHENTICATED = 'already_authenticated';

    public const RATE_LIMITED = 'rate_limited';

    public const INVALID_SELECTION = 'invalid_selection';

    public const PANEL_ACCESS_DENIED = 'panel_access_denied';

    public const LOGIN_FAILED = 'login_failed';
}
