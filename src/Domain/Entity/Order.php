<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use InvalidArgumentException;

/**
 * Aggregate Root representing an order.
 * Contains all information needed to calculate shipping cost.
 */
final class Order
{
    private string $id;
    /** @var Product[] */
    private array $products;
    private Weight $totalWeight;
    private Money $cartValue;
    private Country $deliveryCountry;
    private OrderDate $orderDate;

    /**
     * @param Product[] $products
     */
    private function __construct(
        string $id,
        array $products,
        Weight $totalWeight,
        Money $cartValue,
        Country $deliveryCountry,
        OrderDate $orderDate
    ) {
        $this->id = $id;
        $this->products = $products;
        $this->totalWeight = $totalWeight;
        $this->cartValue = $cartValue;
        $this->deliveryCountry = $deliveryCountry;
        $this->orderDate = $orderDate;
    }

    /**
     * Factory method for creating an order from a list of products.
     * Weight and value are calculated automatically.
     *
     * @param Product[] $products
     */
    public static function create(
        string $id,
        array $products,
        Country $deliveryCountry,
        OrderDate $orderDate
    ): self {
        if (empty($products)) {
            throw new InvalidArgumentException('Order must contain at least one product');
        }

        $totalWeight = Weight::zero();
        $cartValue = Money::zero();

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                throw new InvalidArgumentException('All items must be Product instances');
            }
            $totalWeight = $totalWeight->add($product->totalWeight());
            $cartValue = $cartValue->add($product->totalPrice());
        }

        return new self(
            $id,
            $products,
            $totalWeight,
            $cartValue,
            $deliveryCountry,
            $orderDate
        );
    }

    /**
     * Factory method for creating an order with explicit weight and value.
     * Used when we do not need a detailed product list.
     *
     * @param Product[] $products
     */
    public static function withExplicitValues(
        string $id,
        array $products,
        Weight $totalWeight,
        Money $cartValue,
        Country $deliveryCountry,
        OrderDate $orderDate
    ): self {
        return new self(
            $id,
            $products,
            $totalWeight,
            $cartValue,
            $deliveryCountry,
            $orderDate
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return Product[]
     */
    public function products(): array
    {
        return $this->products;
    }

    public function totalWeight(): Weight
    {
        return $this->totalWeight;
    }

    public function cartValue(): Money
    {
        return $this->cartValue;
    }

    public function deliveryCountry(): Country
    {
        return $this->deliveryCountry;
    }

    public function orderDate(): OrderDate
    {
        return $this->orderDate;
    }

    public function productCount(): int
    {
        return array_reduce(
            $this->products,
            fn(int $count, Product $product) => $count + $product->quantity(),
            0
        );
    }
}

