<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentDeveloperLogin\Events;

final readonly class AutoLoginSucceeded
{
    public function __construct(public string $userModel, public string $identifier, public string $panelId, public string $environment, public \DateTimeImmutable $occurredAt, public ?string $ipAddress = null) {}
}
