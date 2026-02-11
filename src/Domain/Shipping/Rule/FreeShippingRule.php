<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Rule;

use App\Domain\Shipping\Config\FreeShippingConfig;
use App\Domain\Shipping\Config\Repository\FreeShippingConfigRepositoryInterface;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\Shipping\ShippingRuleInterface;
use App\Domain\ValueObject\Money;

/**
 * Rule: Free shipping for high-value orders.
 * Applies to countries where cart value >= threshold grants free shipping.
 * Country scope is defined in database configuration.
 */
final class FreeShippingRule implements ShippingRuleInterface
{
    private const string NAME = 'free_shipping';
    // Cached for the lifetime of this rule instance; recreate service if config changes at runtime.
    private ?FreeShippingConfig $cachedConfig = null;

    public function __construct(
        private readonly FreeShippingConfigRepositoryInterface $configRepository,
        private readonly int $priority = 300,
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
        $threshold = $config->threshold();

        return $context->withCost(
            Money::zero($threshold->currency()),
            self::NAME,
            sprintf(
                'Free shipping (cart >= %s)',
                $threshold->format()
            )
        );
    }

    /**
     * Get config with caching to avoid duplicate DB queries.
     */
    private function getConfig(): FreeShippingConfig
    {
        return $this->cachedConfig ??= $this->configRepository->load();
    }
}
