<?php

declare(strict_types=1);

namespace App\Tests\Support\Shipping\Config\Repository;

use App\Domain\Shipping\Config\HalfPriceShippingConfig;
use App\Domain\Shipping\Config\Repository\HalfPriceShippingConfigRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Tests\Support\BaseTestCase;

final class InMemoryHalfPriceShippingConfigRepository implements HalfPriceShippingConfigRepositoryInterface
{
    public static function defaults(BaseTestCase $testCase): self
    {
        // Half-price shipping (50% discount) for US only
        return new self(
            Money::fromDecimal(400.00),
            50,
            [$testCase->usa()]
        );
    }

    /**
     * @param \App\Domain\Entity\Country[] $countries
     */
    public function __construct(
        private Money $threshold,
        private int $discountPercent,
        private array $countries
    ) {
    }

    public function load(): HalfPriceShippingConfig
    {
        return new HalfPriceShippingConfig($this->threshold, $this->discountPercent, $this->countries);
    }
}
