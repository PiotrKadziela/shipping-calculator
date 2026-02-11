<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config\Repository;

use App\Domain\Shipping\Config\FreeShippingConfig;

interface FreeShippingConfigRepositoryInterface
{
    /**
     * Load free shipping configuration.
     */
    public function load(): FreeShippingConfig;
}
