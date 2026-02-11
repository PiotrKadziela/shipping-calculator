<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config\Repository;

use App\Domain\Shipping\Config\HalfPriceShippingConfig;

interface HalfPriceShippingConfigRepositoryInterface
{
    /**
     * Load half-price shipping configuration.
     */
    public function load(): HalfPriceShippingConfig;
}
