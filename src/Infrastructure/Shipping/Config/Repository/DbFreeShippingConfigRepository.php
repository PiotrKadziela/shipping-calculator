<?php

declare(strict_types=1);

namespace App\Infrastructure\Shipping\Config\Repository;

use App\Domain\Shipping\Config\FreeShippingConfig;
use App\Domain\Shipping\Config\Repository\FreeShippingConfigRepositoryInterface;
use App\Domain\Repository\CountryRepositoryInterface;
use App\Domain\ValueObject\Money;
use PDO;
use RuntimeException;

final class DbFreeShippingConfigRepository implements FreeShippingConfigRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CountryRepositoryInterface $countryRepository
    ) {
    }

    public function load(): FreeShippingConfig
    {
        $configId = $this->getActiveConfigId();
        $freeShippingConfig = $this->loadFreeShippingConfig($configId);
        $countryRates = $this->loadCountryRates($freeShippingConfig['id']);

        $countries = [];
        foreach ($countryRates as $row) {
            $country = $this->countryRepository->findByCode($row['country_code']);
            if ($country !== null) {
                $countries[] = $country;
            }
        }

        $threshold = Money::fromDecimal(
            (float) $freeShippingConfig['threshold'],
            (string) $freeShippingConfig['currency_code']
        );

        return new FreeShippingConfig($threshold, $countries);
    }

    /**
     * Load free shipping config for active shipping config.
        *
        * @return array<string, mixed>
     */
    private function loadFreeShippingConfig(int $configId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, threshold, currency_code 
             FROM free_shipping_configs 
             WHERE config_id = :config_id AND is_enabled = 1'
        );

        $stmt->execute(['config_id' => $configId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Free shipping configuration not found');
        }

        return $row;
    }

    /**
     * Load country-specific rates from rule_scopes.
        *
        * @return list<array<string, mixed>>
     */
    private function loadCountryRates(int $freeShippingConfigId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.code AS country_code
             FROM rule_scopes rs
             INNER JOIN countries c ON c.id = rs.country_id
             WHERE rs.rule_type = :rule_type AND rs.rule_id = :rule_id'
        );

        $stmt->execute([
            'rule_type' => 'free_shipping',
            'rule_id' => $freeShippingConfigId,
        ]);

        return $stmt->fetchAll();
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
