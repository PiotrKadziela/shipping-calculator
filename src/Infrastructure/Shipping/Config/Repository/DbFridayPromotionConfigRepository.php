<?php

declare(strict_types=1);

namespace App\Infrastructure\Shipping\Config\Repository;

use App\Domain\Shipping\Config\FridayPromotionConfig;
use App\Domain\Shipping\Config\Repository\FridayPromotionConfigRepositoryInterface;
use PDO;
use RuntimeException;

final class DbFridayPromotionConfigRepository implements FridayPromotionConfigRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function load(): FridayPromotionConfig
    {
        $configId = $this->getActiveConfigId();
        $config = $this->loadFridayPromotionConfig($configId);

        $discountPercent = (int) $config['discount_percent'];

        return new FridayPromotionConfig($discountPercent);
    }

    /**
     * Load friday promotion config for active shipping config.
        *
        * @return array<string, mixed>
     */
    private function loadFridayPromotionConfig(int $configId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT discount_percent 
             FROM friday_promotion_configs 
             WHERE config_id = :config_id AND is_enabled = 1'
        );

        $stmt->execute(['config_id' => $configId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Friday promotion configuration not found');
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
