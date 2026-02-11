<?php

declare(strict_types=1);

namespace App\Domain\Shipping;

/**
 * Interface for shipping cost calculation rules.
 *
 * Each business rule should implement this interface.
 * Adding a new rule requires only creating a new class implementing
 * this interface and registering it in the DI container.
 *
 * Rules are executed in the order defined by getPriority().
 * Lower priority = executed earlier.
 */
interface ShippingRuleInterface
{
    /**
     * Rule name used for identification and logging.
     */
    public function getName(): string;

    /**
     * Rule priority. Lower priority = executed earlier.
     * Recommended values:
     * - 100: base rates
     * - 200: surcharges (weight, bulky)
     * - 300: value promotions
     * - 400: time promotions
     */
    public function getPriority(): int;

    /**
     * Checks whether the rule applies for the given context.
     */
    public function supports(ShippingCalculationContext $context): bool;

    /**
     * Applies the rule and returns a new context with the updated cost.
     * The context is immutable - the method always returns a new instance.
     */
    public function apply(ShippingCalculationContext $context): ShippingCalculationContext;
}

