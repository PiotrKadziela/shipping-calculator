<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Money;

/**
 * Event emitted after the shipping cost calculation completes.
 */
final readonly class ShippingCostCalculatedEvent extends AbstractDomainEvent
{
    /**
     * @param string[] $appliedRules
     */
    public function __construct(
        private string $orderId,
        private Money $firstRuleCost,
        private Money $finalCost,
        private array $appliedRules
    ) {
        parent::__construct();
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function firstRuleCost(): Money
    {
        return $this->firstRuleCost;
    }

    public function finalCost(): Money
    {
        return $this->finalCost;
    }

    /**
     * @return string[]
     */
    public function appliedRules(): array
    {
        return $this->appliedRules;
    }
}


