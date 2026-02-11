<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config;

use App\Domain\Entity\Country;
use App\Domain\ValueObject\Money;

/**
 * Value Object containing half-price shipping configuration for HalfPriceShippingRule.
 * Immutable and holds the threshold, discount percentage, and applicable countries.
 */
final readonly class HalfPriceShippingConfig
{
    /**
     * @param Money     $threshold       Cart value to get half-price shipping
     * @param int       $discountPercent Percentage discount (typically 50 for half-price)
     * @param Country[] $countries       List of countries where half-price applies
     */
    public function __construct(
        private Money $threshold,
        private int $discountPercent,
        private array $countries,
    ) {}

    public function threshold(): Money
    {
        return $this->threshold;
    }

    public function discountPercent(): int
    {
        return $this->discountPercent;
    }

    /**
     * @return Country[]
     */
    public function countries(): array
    {
        return $this->countries;
    }

    /**
     * Check if a given country is eligible for half-price shipping.
     */
    public function appliesToCountry(Country $country): bool
    {
        foreach ($this->countries as $eligibleCountry) {
            if ($eligibleCountry->code() === $country->code()) {
                return true;
            }
        }

        return false;
    }
}
