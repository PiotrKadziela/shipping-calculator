<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Rule;

use App\Domain\Shipping\Config\Repository\WeightSurchargeConfigRepositoryInterface;
use App\Domain\Shipping\Config\WeightSurchargeConfig;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\Shipping\ShippingRuleInterface;
use App\Domain\ValueObject\Weight;

/**
 * Rule: Weight surcharge.
 *
 * - Up to 5 kg inclusive: no surcharge
 * - Above 5 kg: +3 PLN for each "started" kilogram
 *
 * Example: 7.2 kg package -> 3 "started" kilograms above the limit -> +9 PLN
 */
final class WeightSurchargeRule implements ShippingRuleInterface
{
    private const string NAME = 'weight_surcharge';
    // Cached for the lifetime of this rule instance; recreate service if config changes at runtime.
    private ?WeightSurchargeConfig $cachedConfig = null;

    public function __construct(
        private readonly WeightSurchargeConfigRepositoryInterface $configRepository,
        private readonly int $priority = 200,
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
        $weightLimit = Weight::fromKilograms($config->limitKg());

        return $context->order()->totalWeight()->isGreaterThan($weightLimit);
    }

    public function apply(ShippingCalculationContext $context): ShippingCalculationContext
    {
        $config = $this->getConfig();
        $weightLimit = Weight::fromKilograms($config->limitKg());
        $excessKilograms = $context->order()->totalWeight()->excessKilogramsAbove($weightLimit);

        $surchargePerKg = $config->surchargePerKg();
        $surcharge = $surchargePerKg->multiply((float) $excessKilograms);

        return $context->withAddedCost(
            $surcharge,
            self::NAME,
            sprintf(
                'Weight surcharge: %d excess kg(s) above %s limit = +%s',
                $excessKilograms,
                $config->limitKg(),
                $surcharge->format()
            )
        );
    }

    /**
     * Get config with caching to avoid duplicate DB queries.
     */
    private function getConfig(): WeightSurchargeConfig
    {
        return $this->cachedConfig ??= $this->configRepository->load();
    }
}
