<?php

declare(strict_types=1);

namespace App\Infrastructure\Shipping\Config\Repository;

use App\Domain\Repository\CountryRepositoryInterface;
use App\Domain\Shipping\Config\BaseCountryRateConfig;
use App\Domain\Shipping\Config\Repository\BaseCountryRateConfigRepositoryInterface;
use App\Domain\ValueObject\Money;

final class DbBaseCountryRateConfigRepository implements BaseCountryRateConfigRepositoryInterface
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly CountryRepositoryInterface $countryRepository,
    ) {}

    public function load(): BaseCountryRateConfig
    {
        $configId = $this->getActiveConfigId();
        $baseRateConfig = $this->loadBaseRateConfig($configId);
        $countryRates = $this->loadCountryRates($baseRateConfig['id']);

        $rates = [];
        foreach ($countryRates as $row) {
            $country = $this->countryRepository->findByCode($row['country_code']);
            if (null !== $country) {
                $rates[$country->code()] = Money::fromDecimal(
                    (float) $row['amount'],
                    (string) $row['currency_code']
                );
            }
        }

        $defaultRate = Money::fromDecimal(
            (float) $baseRateConfig['default_amount'],
            (string) $baseRateConfig['currency_code']
        );

        return new BaseCountryRateConfig($rates, $defaultRate);
    }

    /**
     * Load base rate config for active shipping config.
     *
     * @return array<string, mixed>
     */
    private function loadBaseRateConfig(int $configId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, default_amount, currency_code 
             FROM base_rate_configs 
             WHERE config_id = :config_id AND is_enabled = 1'
        );

        $stmt->execute(['config_id' => $configId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException('Base rate configuration not found');
        }

        return $row;
    }

    /**
     * Load country-specific rates from rule_scopes.
     * Uses country-specific amounts if available, otherwise uses default amount.
     *
     * @return list<array<string, mixed>>
     */
    private function loadCountryRates(int $baseRateConfigId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.code AS country_code, COALESCE(rs.amount, br.default_amount) AS amount, br.currency_code
             FROM rule_scopes rs
             INNER JOIN countries c ON c.id = rs.country_id
             INNER JOIN base_rate_configs br ON br.id = :base_rate_id
             WHERE rs.rule_type = :rule_type AND rs.rule_id = :rule_id'
        );

        $stmt->execute([
            'base_rate_id' => $baseRateConfigId,
            'rule_type' => 'base_rate',
            'rule_id' => $baseRateConfigId,
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
            throw new \RuntimeException('No active shipping configuration found');
        }

        return (int) $row['id'];
    }
}
