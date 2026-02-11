<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Config;

/**
 * Value Object containing Friday promotion configuration for FridayPromotionRule.
 * Immutable and holds the discount percentage applied on Fridays.
 */
final readonly class FridayPromotionConfig
{
    /**
     * @param int $discountPercent Percentage discount on Fridays (typically 50)
     */
    public function __construct(
        private int $discountPercent
    ) {
    }

    public function discountPercent(): int
    {
        return $this->discountPercent;
    }
}
