<?php

declare(strict_types=1);

namespace App\Tests\Support\Shipping\Config\Repository;

use App\Domain\Shipping\Config\FreeShippingConfig;
use App\Domain\Shipping\Config\Repository\FreeShippingConfigRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Tests\Support\BaseTestCase;

final class InMemoryFreeShippingConfigRepository implements FreeShippingConfigRepositoryInterface
{
    public static function defaults(BaseTestCase $testCase): self
    {
        // Free shipping for PL, DE, FR, GB (not US)
        return new self(
            Money::fromDecimal(400.00),
            [
                $testCase->poland(),
                $testCase->germany(),
                $testCase->france(),
                $testCase->unitedKingdom(),
            ]
        );
    }

    /**
     * @param \App\Domain\Entity\Country[] $countries
     */
    public function __construct(
        private Money $threshold,
        private array $countries,
    ) {}

    public function load(): FreeShippingConfig
    {
        return new FreeShippingConfig($this->threshold, $this->countries);
    }
}
