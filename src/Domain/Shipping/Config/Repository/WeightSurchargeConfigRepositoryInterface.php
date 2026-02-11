<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config\Repository;

use App\Domain\Shipping\Config\WeightSurchargeConfig;

interface WeightSurchargeConfigRepositoryInterface
{
    /**
     * Load weight surcharge configuration.
     */
    public function load(): WeightSurchargeConfig;
}
