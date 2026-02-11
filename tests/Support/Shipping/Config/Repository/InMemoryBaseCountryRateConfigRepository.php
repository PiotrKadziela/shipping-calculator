<?php

declare(strict_types=1);

namespace App\Tests\Support\Shipping\Config\Repository;

use App\Domain\Shipping\Config\BaseCountryRateConfig;
use App\Domain\Shipping\Config\Repository\BaseCountryRateConfigRepositoryInterface;
use App\Domain\ValueObject\Money;

final class InMemoryBaseCountryRateConfigRepository implements BaseCountryRateConfigRepositoryInterface
{
    public static function defaults(): self
    {
        return new self(
            [
                'PL' => Money::fromDecimal(10.00),
                'DE' => Money::fromDecimal(20.00),
                'US' => Money::fromDecimal(50.00),
            ],
            Money::fromDecimal(39.99)
        );
    }

    public function __construct(
        private array $baseRates,
        private Money $defaultRate
    ) {
    }

    public function load(): BaseCountryRateConfig
    {
        return new BaseCountryRateConfig($this->baseRates, $this->defaultRate);
    }
}
