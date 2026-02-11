<?php

declare(strict_types=1);

namespace App\Infrastructure\Shipping\Config\Repository;

use App\Domain\Shipping\Config\WeightSurchargeConfig;
use App\Domain\Shipping\Config\Repository\WeightSurchargeConfigRepositoryInterface;
use App\Domain\ValueObject\Money;
use PDO;
use RuntimeException;

final class DbWeightSurchargeConfigRepository implements WeightSurchargeConfigRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function load(): WeightSurchargeConfig
    {
        $configId = $this->getActiveConfigId();
        $config = $this->loadWeightSurchargeConfig($configId);

        $limitKg = (float) $config['limit_kg'];
        $surchargePerKg = Money::fromDecimal(
            (float) $config['surcharge_per_kg'],
            (string) $config['currency_code']
        );

        return new WeightSurchargeConfig($limitKg, $surchargePerKg);
    }

    /**
     * Load weight surcharge config for active shipping config.
        *
        * @return array<string, mixed>
     */
    private function loadWeightSurchargeConfig(int $configId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT limit_kg, surcharge_per_kg, currency_code 
             FROM weight_surcharge_configs 
             WHERE config_id = :config_id AND is_enabled = 1'
        );

        $stmt->execute(['config_id' => $configId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Weight surcharge configuration not found');
        }

        return $row;
    }

    private function getActiveConfigId(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM shipping_configs WHERE is_active = 1 ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('No active shipping configuration found');
        }

        return (int) $row['id'];
    }
}
