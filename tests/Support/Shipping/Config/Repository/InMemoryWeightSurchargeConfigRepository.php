<?php

declare(strict_types=1);

namespace App\Tests\Support\Shipping\Config\Repository;

use App\Domain\Shipping\Config\WeightSurchargeConfig;
use App\Domain\Shipping\Config\Repository\WeightSurchargeConfigRepositoryInterface;
use App\Domain\ValueObject\Money;

final class InMemoryWeightSurchargeConfigRepository implements WeightSurchargeConfigRepositoryInterface
{
    public static function defaults(): self
    {
        return new self(5.0, Money::fromDecimal(3.00));
    }

    public function __construct(
        private float $limitKg,
        private Money $surchargePerKg
    ) {
    }

    public function load(): WeightSurchargeConfig
    {
        return new WeightSurchargeConfig($this->limitKg, $this->surchargePerKg);
    }
}
