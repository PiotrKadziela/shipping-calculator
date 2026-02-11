<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Rule;

use App\Domain\Shipping\Config\BaseCountryRateConfig;
use App\Domain\Shipping\Config\Repository\BaseCountryRateConfigRepositoryInterface;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\Shipping\ShippingRuleInterface;

/**
 * Rule: Base rates by country.
 *
 * Poland -> 10 PLN
 * Germany -> 20 PLN
 * USA -> 50 PLN
 * Other countries -> 39.99 PLN
 */
final class BaseCountryRateRule implements ShippingRuleInterface
{
    private const string NAME = 'base_country_rate';
    // Cached for the lifetime of this rule instance; recreate service if config changes at runtime.
    private ?BaseCountryRateConfig $cachedConfig = null;

    public function __construct(
        private readonly BaseCountryRateConfigRepositoryInterface $configRepository,
        private readonly int $priority = 100,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function supports(ShippingCalculationContext $context): bool
    {
        // Always apply the base rate
        return true;
    }

    public function apply(ShippingCalculationContext $context): ShippingCalculationContext
    {
        $country = $context->order()->deliveryCountry();
        $config = $this->getConfig();
        $rate = $config->getRateForCountry($country->code());

        return $context->withCost(
            $rate,
            self::NAME,
            sprintf('Base shipping rate for %s: %s', $country->name(), $rate->format())
        );
    }

    /**
     * Get config with caching to avoid duplicate DB queries.
     */
    private function getConfig(): BaseCountryRateConfig
    {
        return $this->cachedConfig ??= $this->configRepository->load();
    }
}
