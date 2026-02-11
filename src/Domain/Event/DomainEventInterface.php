<?php

declare(strict_types=1);

namespace App\Domain\Event;

/**
 * Bazowy interfejs dla wszystkich Domain Events.
 */
interface DomainEventInterface
{
    public function occurredAt(): \DateTimeImmutable;
}

