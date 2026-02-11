<?php

declare(strict_types=1);

namespace App\Infrastructure\Shipping;

use App\Domain\Shipping\Config\Repository\BaseCountryRateConfigRepositoryInterface;
use App\Domain\Shipping\Config\Repository\FreeShippingConfigRepositoryInterface;
use App\Domain\Shipping\Config\Repository\FridayPromotionConfigRepositoryInterface;
use App\Domain\Shipping\Config\Repository\HalfPriceShippingConfigRepositoryInterface;
use App\Domain\Shipping\Config\Repository\WeightSurchargeConfigRepositoryInterface;
use App\Domain\Shipping\Rule\BaseCountryRateRule;
use App\Domain\Shipping\Rule\FreeShippingRule;
use App\Domain\Shipping\Rule\FridayPromotionRule;
use App\Domain\Shipping\Rule\HalfPriceShippingRule;
use App\Domain\Shipping\Rule\WeightSurchargeRule;
use App\Domain\Shipping\ShippingRuleInterface;

/**
 * Factory for creating shipping rules with priorities loaded from database.
 */
final class ShippingRuleFactory
{
    /** @var array<string, int>|null */
    private ?array $priorities = null;

    public function __construct(
        private readonly \PDO $pdo,
        private readonly BaseCountryRateConfigRepositoryInterface $baseCountryRateConfig,
        private readonly WeightSurchargeConfigRepositoryInterface $weightSurchargeConfig,
        private readonly FreeShippingConfigRepositoryInterface $freeShippingConfig,
        private readonly HalfPriceShippingConfigRepositoryInterface $halfPriceShippingConfig,
        private readonly FridayPromotionConfigRepositoryInterface $fridayPromotionConfig,
    ) {}

    /**
     * @return ShippingRuleInterface[]
     */
    public function createRules(): array
    {
        $priorities = $this->loadPriorities();

        return [
            new BaseCountryRateRule(
                $this->baseCountryRateConfig,
                $priorities['base_rate'] ?? 100
            ),
            new WeightSurchargeRule(
                $this->weightSurchargeConfig,
                $priorities['weight_surcharge'] ?? 200
            ),
            new FreeShippingRule(
                $this->freeShippingConfig,
                $priorities['free_shipping_promo'] ?? 300
            ),
            new HalfPriceShippingRule(
                $this->halfPriceShippingConfig,
                $priorities['half_price_shipping_promo'] ?? 300
            ),
            new FridayPromotionRule(
                $this->fridayPromotionConfig,
                $priorities['friday_promo'] ?? 400
            ),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function loadPriorities(): array
    {
        if (null !== $this->priorities) {
            return $this->priorities;
        }

        // Load all priorities in single query using UNION
        $configId = $this->getActiveConfigId();

        $stmt = $this->pdo->prepare(
            <<<SQL
                SELECT 'base_rate' as rule_type, COALESCE(priority, 100) as priority
                FROM base_rate_configs
                WHERE config_id = ? AND is_enabled = 1

                UNION

                SELECT 'weight_surcharge' as rule_type, COALESCE(priority, 200) as priority
                FROM weight_surcharge_configs
                WHERE config_id = ? AND is_enabled = 1

                UNION

                SELECT 'free_shipping_promo' as rule_type, COALESCE(priority, 300) as priority
                FROM free_shipping_configs
                WHERE config_id = ? AND is_enabled = 1

                UNION

                SELECT 'half_price_shipping_promo' as rule_type, COALESCE(priority, 305) as priority
                FROM half_price_shipping_configs
                WHERE config_id = ? AND is_enabled = 1

                UNION

                SELECT 'friday_promo' as rule_type, COALESCE(priority, 400) as priority
                FROM friday_promotion_configs
                WHERE config_id = ? AND is_enabled = 1
                SQL
        );

        $stmt->execute([$configId, $configId, $configId, $configId, $configId]);

        $priorities = [];
        while ($row = $stmt->fetch()) {
            $priorities[$row['rule_type']] = (int) $row['priority'];
        }

        $this->priorities = $priorities;

        return $priorities;
    }

    private function getActiveConfigId(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM shipping_configs WHERE is_active = 1 ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException('No active shipping configuration found');
        }

        return (int) $row['id'];
    }
}
