<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config;

use App\Domain\ValueObject\Money;

/**
 * Value Object containing base rate configuration for BaseCountryRateRule.
 * Immutable and holds rates for each country plus a default rate.
 */
final readonly class BaseCountryRateConfig
{
    /**
     * @param array<string, Money> $baseRates   Country code => Money rate
     * @param Money                $defaultRate Rate for countries not explicitly configured
     */
    public function __construct(
        private array $baseRates,
        private Money $defaultRate,
    ) {}

    /**
     * Get rate for a specific country code.
     * Returns default rate if country not explicitly configured.
     */
    public function getRateForCountry(string $countryCode): Money
    {
        if (isset($this->baseRates[$countryCode])) {
            return $this->baseRates[$countryCode];
        }

        return $this->defaultRate;
    }

    /**
     * @return array<string, Money>
     */
    public function baseRates(): array
    {
        return $this->baseRates;
    }

    public function defaultRate(): Money
    {
        return $this->defaultRate;
    }
}
