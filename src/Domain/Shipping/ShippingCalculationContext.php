<?php

declare(strict_types=1);

namespace App\Domain\Shipping;

use App\Domain\Entity\Order;
use App\Domain\Event\DomainEventInterface;
use App\Domain\Event\ShippingRuleAppliedEvent;
use App\Domain\ValueObject\Money;

/**
 * Shipping calculation context.
 * Immutable object that carries calculation state between rules.
 * Contains domain events emitted during calculation.
 */
final readonly class ShippingCalculationContext
{
    /**
     * @param DomainEventInterface[] $events
     * @param string[]               $appliedRules
     */
    private function __construct(
        private Order $order,
        private Money $currentCost,
        private ?Money $firstRuleCost = null,
        private array $events = [],
        private array $appliedRules = [],
    ) {}

    public static function forOrder(Order $order): self
    {
        return new self($order, Money::zero(), null, [], []);
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function currentCost(): Money
    {
        return $this->currentCost;
    }

    /**
     * Cost after the first applied rule.
     * Returns current cost if no rule has been applied yet.
     */
    public function firstRuleCost(): Money
    {
        return $this->firstRuleCost ?? $this->currentCost;
    }

    /**
     * @return DomainEventInterface[]
     */
    public function events(): array
    {
        return $this->events;
    }

    /**
     * @return string[]
     */
    public function appliedRules(): array
    {
        return $this->appliedRules;
    }

    public function withCost(Money $newCost, string $ruleName, string $description): self
    {
        $event = new ShippingRuleAppliedEvent(
            $this->order->id(),
            $ruleName,
            $this->currentCost,
            $newCost,
            $description
        );

        // Track cost after the first applied rule
        $newFirstRuleCost = $this->firstRuleCost ?? $newCost;

        return new self(
            $this->order,
            $newCost,
            $newFirstRuleCost,
            [...$this->events, $event],
            [...$this->appliedRules, $ruleName]
        );
    }

    public function withAddedCost(Money $additionalCost, string $ruleName, string $description): self
    {
        return $this->withCost(
            $this->currentCost->add($additionalCost),
            $ruleName,
            $description
        );
    }

    public function isFreeShipping(): bool
    {
        return $this->currentCost->isZero();
    }

    public function hasAppliedRule(string $ruleName): bool
    {
        return in_array($ruleName, $this->appliedRules, true);
    }
}
