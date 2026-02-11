<?php

declare(strict_types=1);

namespace App\Infrastructure\Shipping;

use App\Domain\Shipping\Config\Repository\BaseCountryRateConfigRepositoryInterface;
use App\Domain\Shipping\Config\Repository\FreeShippingConfigRepositoryInterface;
use App\Domain\Shipping\Config\Repository\FridayPromotionConfigRepositoryInterface;
use App\Domain\Shipping\Config\Repository\HalfPriceShippingConfigRepositoryInterface;
use App\Domain\Shipping\Config\Repository\WeightSurchargeConfigRepositoryInterface;
use App\Domain\Shipping\ShippingRuleInterface;
use App\Domain\Shipping\Rule\BaseCountryRateRule;
use App\Domain\Shipping\Rule\FreeShippingRule;
use App\Domain\Shipping\Rule\FridayPromotionRule;
use App\Domain\Shipping\Rule\HalfPriceShippingRule;
use App\Domain\Shipping\Rule\WeightSurchargeRule;
use PDO;

/**
 * Factory for creating shipping rules with priorities loaded from database.
 */
final class ShippingRuleFactory
{
    /** @var array<string, int>|null */
    private ?array $priorities = null;

    public function __construct(
        private readonly PDO $pdo,
        private readonly BaseCountryRateConfigRepositoryInterface $baseCountryRateConfig,
        private readonly WeightSurchargeConfigRepositoryInterface $weightSurchargeConfig,
        private readonly FreeShippingConfigRepositoryInterface $freeShippingConfig,
        private readonly HalfPriceShippingConfigRepositoryInterface $halfPriceShippingConfig,
        private readonly FridayPromotionConfigRepositoryInterface $fridayPromotionConfig
    ) {
    }

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
        if ($this->priorities !== null) {
            return $this->priorities;
        }

        $priorities = [];

        // Load priorities from each dedicated config table for active shipping config
        $configId = $this->getActiveConfigId();

        // Load base_rate priority
        $stmt = $this->pdo->prepare(
            'SELECT MIN(priority) as priority FROM base_rate_configs WHERE config_id = ? AND is_enabled = 1'
        );
        $stmt->execute([$configId]);
        if ($row = $stmt->fetch()) {
            $priorities['base_rate'] = (int) ($row['priority'] ?? 100);
        } else {
            $priorities['base_rate'] = 100;
        }

        // Load weight_surcharge priority
        $stmt = $this->pdo->prepare(
            'SELECT MIN(priority) as priority FROM weight_surcharge_configs WHERE config_id = ? AND is_enabled = 1'
        );
        $stmt->execute([$configId]);
        if ($row = $stmt->fetch()) {
            $priorities['weight_surcharge'] = (int) ($row['priority'] ?? 200);
        } else {
            $priorities['weight_surcharge'] = 200;
        }

        // Load free_shipping priority
        $stmt = $this->pdo->prepare(
            'SELECT MIN(priority) as priority FROM free_shipping_configs WHERE config_id = ? AND is_enabled = 1'
        );
        $stmt->execute([$configId]);
        if ($row = $stmt->fetch()) {
            $priorities['free_shipping_promo'] = (int) ($row['priority'] ?? 300);
        } else {
            $priorities['free_shipping_promo'] = 300;
        }

        // Load half_price_shipping priority
        $stmt = $this->pdo->prepare(
            'SELECT MIN(priority) as priority FROM half_price_shipping_configs WHERE config_id = ? AND is_enabled = 1'
        );
        $stmt->execute([$configId]);
        if ($row = $stmt->fetch()) {
            $priorities['half_price_shipping_promo'] = (int) ($row['priority'] ?? 305);
        } else {
            $priorities['half_price_shipping_promo'] = 305;
        }

        // Load friday_promo priority
        $stmt = $this->pdo->prepare(
            'SELECT MIN(priority) as priority FROM friday_promotion_configs WHERE config_id = ? AND is_enabled = 1'
        );
        $stmt->execute([$configId]);
        if ($row = $stmt->fetch()) {
            $priorities['friday_promo'] = (int) ($row['priority'] ?? 400);
        } else {
            $priorities['friday_promo'] = 400;
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

