<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config;

use App\Domain\Entity\Country;
use App\Domain\ValueObject\Money;

/**
 * Value Object containing free shipping configuration for FreeShippingRule.
 * Immutable and holds the threshold for free shipping and applicable countries.
 */
final readonly class FreeShippingConfig
{
    /**
     * @param Money $threshold Cart value to get free shipping
     * @param Country[] $countries List of countries where free shipping applies
     */
    public function __construct(
        private Money $threshold,
        private array $countries
    ) {
    }

    public function threshold(): Money
    {
        return $this->threshold;
    }

    /**
     * @return Country[]
     */
    public function countries(): array
    {
        return $this->countries;
    }

    /**
     * Check if a given country is eligible for free shipping.
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
