<?php

declare(strict_types=1);

namespace App\Tests\Support\Shipping\Config\Repository;

use App\Domain\Shipping\Config\FridayPromotionConfig;
use App\Domain\Shipping\Config\Repository\FridayPromotionConfigRepositoryInterface;

final class InMemoryFridayPromotionConfigRepository implements FridayPromotionConfigRepositoryInterface
{
    public static function defaults(): self
    {
        return new self(50);
    }

    public function __construct(private int $discountPercent)
    {
    }

    public function load(): FridayPromotionConfig
    {
        return new FridayPromotionConfig($this->discountPercent);
    }
}
