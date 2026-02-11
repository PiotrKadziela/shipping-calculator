<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Country;
use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use App\Tests\Support\BaseTestCase;

final class OrderTest extends BaseTestCase
{
    #[Test]
    public function it_creates_order_with_calculated_values(): void
    {
        // Given
        $products = [
            new Product('p1', 'Laptop', Money::fromCents(250000), Weight::fromKilograms(2.5), 1),
            new Product('p2', 'Mouse', Money::fromCents(10000), Weight::fromGrams(200), 2),
        ];

        // When
        $order = Order::create(
            'order_1',
            $products,
            $this->poland(),
            OrderDate::fromString('2024-01-15')
        );

        // Then
        self::assertSame('order_1', $order->id());
        self::assertCount(2, $order->products());
        // Weight: 2.5kg + 2*0.2kg = 2.9kg = 2900g
        self::assertSame(2900, $order->totalWeight()->grams());
        // Value: 2500 + 2*100 = 2700 PLN = 270000 cents
        self::assertSame(270000, $order->cartValue()->amountInCents());
        self::assertSame('PL', $order->deliveryCountry()->code());
    }

    #[Test]
    public function it_creates_order_with_explicit_values(): void
    {
        // Given
        $products = [new Product('p1', 'Test', Money::fromCents(10000), Weight::fromKilograms(1))];

        // When
        $order = Order::withExplicitValues(
            'order_1',
            $products,
            Weight::fromKilograms(5),
            Money::fromCents(50000),
            $this->germany(),
            OrderDate::fromString('2024-01-19')
        );

        // Then
        self::assertSame(5000, $order->totalWeight()->grams());
        self::assertSame(50000, $order->cartValue()->amountInCents());
    }

    #[Test]
    public function it_throws_on_empty_products(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order must contain at least one product');

        Order::create(
            'order_1',
            [],
            $this->poland(),
            OrderDate::fromString('2024-01-15')
        );
    }

    #[Test]
    public function it_throws_on_invalid_product_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All items must be Product instances');

        /** @phpstan-ignore-next-line Testing runtime validation */
        Order::create(
            'order_1',
            ['not a product'],
            $this->poland(),
            OrderDate::fromString('2024-01-15')
        );
    }

    #[Test]
    public function it_calculates_product_count_with_quantities(): void
    {
        // Given
        $products = [
            new Product('p1', 'Item 1', Money::fromCents(1000), Weight::fromGrams(100), 3),
            new Product('p2', 'Item 2', Money::fromCents(2000), Weight::fromGrams(200), 2),
        ];

        // When
        $order = Order::create(
            'order_1',
            $products,
            $this->poland(),
            OrderDate::fromString('2024-01-15')
        );

        // Then
        self::assertSame(5, $order->productCount()); // 3 + 2
    }
}


