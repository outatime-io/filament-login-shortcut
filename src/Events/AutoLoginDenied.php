<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Events;

final readonly class AutoLoginDenied
{
    public function __construct(public string $reason, public string $panelId, public string $environment, public \DateTimeImmutable $occurredAt, public ?string $ipAddress = null) {}
}
