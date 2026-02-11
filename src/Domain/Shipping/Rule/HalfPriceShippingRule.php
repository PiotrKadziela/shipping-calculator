<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Rule;

use App\Domain\Shipping\Config\HalfPriceShippingConfig;
use App\Domain\Shipping\Config\Repository\HalfPriceShippingConfigRepositoryInterface;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\Shipping\ShippingRuleInterface;

/**
 * Rule: Half-price shipping for high-value orders.
 * Applies to countries where cart value >= threshold grants 50% discount.
 * Country scope is defined in database configuration.
 */
final class HalfPriceShippingRule implements ShippingRuleInterface
{
    private const string NAME = 'half_price_shipping';
    // Cached for the lifetime of this rule instance; recreate service if config changes at runtime.
    private ?HalfPriceShippingConfig $cachedConfig = null;

    public function __construct(
        private readonly HalfPriceShippingConfigRepositoryInterface $configRepository,
        private readonly int $priority = 305,
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
        $config = $this->getConfig();
        if (!$config->appliesToCountry($context->order()->deliveryCountry())) {
            return false;
        }

        return $context->order()->cartValue()->isGreaterThanOrEqual($config->threshold());
    }

    public function apply(ShippingCalculationContext $context): ShippingCalculationContext
    {
        $config = $this->getConfig();
        $discountedCost = $context->currentCost()->percentage(100 - $config->discountPercent());

        return $context->withCost(
            $discountedCost,
            self::NAME,
            sprintf(
                'Half-price shipping (cart >= %s): %d%% discount = %s',
                $config->threshold()->format(),
                $config->discountPercent(),
                $discountedCost->format()
            )
        );
    }

    /**
     * Get config with caching to avoid duplicate DB queries.
     */
    private function getConfig(): HalfPriceShippingConfig
    {
        return $this->cachedConfig ??= $this->configRepository->load();
    }
}
