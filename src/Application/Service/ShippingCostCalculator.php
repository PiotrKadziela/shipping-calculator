<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Order;
use App\Domain\Event\ShippingCostCalculatedEvent;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\Shipping\ShippingRuleInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Application Service responsible for shipping cost calculation.
 *
 * Uses the Chain of Responsibility pattern with rules registered via
 * Dependency Injection. New rules can be added without modifying this
 * code (Open/Closed Principle).
 */
final readonly class ShippingCostCalculator
{
    /**
     * @param iterable<ShippingRuleInterface> $rules
     */
    public function __construct(
        private iterable $rules,
        private ?EventDispatcherInterface $eventDispatcher = null
    ) {
    }

    public function calculate(Order $order): ShippingCalculationResult
    {
        $context = ShippingCalculationContext::forOrder($order);
        $sortedRules = $this->getSortedRules();

        foreach ($sortedRules as $rule) {
            if ($rule->supports($context)) {
                $context = $rule->apply($context);
            }
        }

        $result = new ShippingCalculationResult(
            $order,
            $context->currentCost(),
            $context->appliedRules(),
            $context->events()
        );

        $this->dispatchEvents($context, $result);

        return $result;
    }

    /**
     * @return ShippingRuleInterface[]
     */
    private function getSortedRules(): array
    {
        $rules = iterator_to_array($this->rules);

        usort($rules, function(ShippingRuleInterface $a, ShippingRuleInterface $b): int {
            $priorityComparison = $a->getPriority() <=> $b->getPriority();
            
            // If priorities are equal, sort by rule name for deterministic ordering
            if ($priorityComparison === 0) {
                return $a->getName() <=> $b->getName();
            }
            
            return $priorityComparison;
        });

        return $rules;
    }

    private function dispatchEvents(ShippingCalculationContext $context, ShippingCalculationResult $result): void
    {
        if ($this->eventDispatcher === null) {
            return;
        }

        // Dispatch all rule application events
        foreach ($context->events() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        // Dispatch final calculation completed event with tracked first rule cost
        $this->eventDispatcher->dispatch(new ShippingCostCalculatedEvent(
            $result->order()->id(),
            $context->firstRuleCost(),
            $result->shippingCost(),
            $result->appliedRules()
        ));
    }
}

