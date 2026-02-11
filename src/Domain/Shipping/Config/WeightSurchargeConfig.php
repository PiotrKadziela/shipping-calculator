<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config;

use App\Domain\ValueObject\Money;

/**
 * Value Object containing weight surcharge configuration for WeightSurchargeRule.
 * Immutable and holds the weight limit and surcharge amount.
 */
final readonly class WeightSurchargeConfig
{
    /**
     * @param float $limitKg        Weight limit in kilograms (below limit = no charge)
     * @param Money $surchargePerKg Surcharge amount per started kilogram above limit
     */
    public function __construct(
        private float $limitKg,
        private Money $surchargePerKg,
    ) {}

    public function limitKg(): float
    {
        return $this->limitKg;
    }

    public function surchargePerKg(): Money
    {
        return $this->surchargePerKg;
    }
}
