<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Weight;

/**
 * Entity representing a product in an order.
 */
final readonly class Product
{
    public function __construct(
        private string $id,
        private string $name,
        private Money $price,
        private Weight $weight,
        private int $quantity = 1
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function weight(): Weight
    {
        return $this->weight;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function totalPrice(): Money
    {
        return $this->price->multiply($this->quantity);
    }

    public function totalWeight(): Weight
    {
        return Weight::fromGrams($this->weight->grams() * $this->quantity);
    }
}

