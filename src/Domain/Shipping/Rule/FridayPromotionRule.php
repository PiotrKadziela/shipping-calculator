<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Rule;

use App\Domain\Shipping\Config\FridayPromotionConfig;
use App\Domain\Shipping\Config\Repository\FridayPromotionConfigRepositoryInterface;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\Shipping\ShippingRuleInterface;

/**
 * Rule: Friday promotion.
 *
 * - Every Friday shipping cost is reduced by 50%
 * - Not applied if shipping is already free
 * - Stacks with the USA discount (USA discount first, then Friday discount)
 */
final class FridayPromotionRule implements ShippingRuleInterface
{
    private const string NAME = 'friday_promotion';
    // Cached for the lifetime of this rule instance; recreate service if config changes at runtime.
    private ?FridayPromotionConfig $cachedConfig = null;

    public function __construct(
        private readonly FridayPromotionConfigRepositoryInterface $configRepository,
        private readonly int $priority = 400
    ) {
    }

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
        // Apply only on Fridays and when shipping is not free
        return $context->order()->orderDate()->isFriday()
            && !$context->isFreeShipping();
    }

    public function apply(ShippingCalculationContext $context): ShippingCalculationContext
    {
        $config = $this->getConfig();
        $discountedCost = $context->currentCost()->percentage(100 - $config->discountPercent());

        return $context->withCost(
            $discountedCost,
            self::NAME,
            sprintf(
                'Friday promotion: %d%% discount = %s',
                $config->discountPercent(),
                $discountedCost->format()
            )
        );
    }

    /**
     * Get config with caching to avoid duplicate DB queries.
     */
    private function getConfig(): FridayPromotionConfig
    {
        return $this->cachedConfig ??= $this->configRepository->load();
    }
}

