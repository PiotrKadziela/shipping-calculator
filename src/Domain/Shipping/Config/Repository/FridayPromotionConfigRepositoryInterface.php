<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config\Repository;

use App\Domain\Shipping\Config\FridayPromotionConfig;

interface FridayPromotionConfigRepositoryInterface
{
    /**
     * Load Friday promotion configuration.
     */
    public function load(): FridayPromotionConfig;
}
