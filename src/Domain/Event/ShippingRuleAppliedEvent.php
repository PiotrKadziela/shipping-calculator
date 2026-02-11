<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Money;

/**
 * Event emitted when a shipping calculation rule is applied.
 */
final readonly class ShippingRuleAppliedEvent extends AbstractDomainEvent
{
    public function __construct(
        private string $orderId,
        private string $ruleName,
        private Money $costBefore,
        private Money $costAfter,
        private string $description
    ) {
        parent::__construct();
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function ruleName(): string
    {
        return $this->ruleName;
    }

    public function costBefore(): Money
    {
        return $this->costBefore;
    }

    public function costAfter(): Money
    {
        return $this->costAfter;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function difference(): Money
    {
        if ($this->costAfter->isGreaterThan($this->costBefore)) {
            return $this->costAfter->subtract($this->costBefore);
        }
        return $this->costBefore->subtract($this->costAfter);
    }
}

