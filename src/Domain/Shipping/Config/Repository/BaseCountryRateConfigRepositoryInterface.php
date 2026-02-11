<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config\Repository;

use App\Domain\Shipping\Config\BaseCountryRateConfig;

interface BaseCountryRateConfigRepositoryInterface
{
    /**
     * Load base country rate configuration.
     */
    public function load(): BaseCountryRateConfig;
}
