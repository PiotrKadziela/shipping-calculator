<?php

declare(strict_types=1);

namespace App\Infrastructure\Shipping\Config\Repository;

use App\Domain\Shipping\Config\HalfPriceShippingConfig;
use App\Domain\Shipping\Config\Repository\HalfPriceShippingConfigRepositoryInterface;
use App\Domain\Repository\CountryRepositoryInterface;
use App\Domain\ValueObject\Money;
use PDO;
use RuntimeException;

final class DbHalfPriceShippingConfigRepository implements HalfPriceShippingConfigRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CountryRepositoryInterface $countryRepository
    ) {
    }

    public function load(): HalfPriceShippingConfig
    {
        $configId = $this->getActiveConfigId();
        $halfPriceConfig = $this->loadHalfPriceShippingConfig($configId);
        $countryRates = $this->loadCountryRates($halfPriceConfig['id']);

        $countries = [];
        foreach ($countryRates as $row) {
            $country = $this->countryRepository->findByCode($row['country_code']);
            if ($country !== null) {
                $countries[] = $country;
            }
        }

        $threshold = Money::fromDecimal(
            (float) $halfPriceConfig['threshold'],
            (string) $halfPriceConfig['currency_code']
        );

        $discountPercent = (int) $halfPriceConfig['discount_percent'];

        return new HalfPriceShippingConfig($threshold, $discountPercent, $countries);
    }

    /**
     * Load half price shipping config for active shipping config.
        *
        * @return array<string, mixed>
     */
    private function loadHalfPriceShippingConfig(int $configId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, threshold, discount_percent, currency_code 
             FROM half_price_shipping_configs 
             WHERE config_id = :config_id AND is_enabled = 1'
        );

        $stmt->execute(['config_id' => $configId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Half price shipping configuration not found');
        }

        return $row;
    }

    /**
     * Load country-specific rates from rule_scopes.
        *
        * @return list<array<string, mixed>>
     */
    private function loadCountryRates(int $halfPriceConfigId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.code AS country_code
             FROM rule_scopes rs
             INNER JOIN countries c ON c.id = rs.country_id
             WHERE rs.rule_type = :rule_type AND rs.rule_id = :rule_id'
        );

        $stmt->execute([
            'rule_type' => 'half_price_shipping',
            'rule_id' => $halfPriceConfigId,
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
