<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Order;
use App\Domain\Event\DomainEventInterface;
use App\Domain\ValueObject\Money;

/**
 * DTO representing the shipping cost calculation result.
 */
final readonly class ShippingCalculationResult
{
    /**
     * @param string[]               $appliedRules
     * @param DomainEventInterface[] $events
     */
    public function __construct(
        private Order $order,
        private Money $shippingCost,
        private array $appliedRules,
        private array $events,
    ) {}

    public function order(): Order
    {
        return $this->order;
    }

    public function shippingCost(): Money
    {
        return $this->shippingCost;
    }

    /**
     * @return string[]
     */
    public function appliedRules(): array
    {
        return $this->appliedRules;
    }

    /**
     * @return DomainEventInterface[]
     */
    public function events(): array
    {
        return $this->events;
    }

    public function isFreeShipping(): bool
    {
        return $this->shippingCost->isZero();
    }
}
