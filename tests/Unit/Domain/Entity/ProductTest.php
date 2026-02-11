<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Product;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Weight;
use App\Tests\Support\BaseTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ProductTest extends BaseTestCase
{
    #[Test]
    public function itCreatesProduct(): void
    {
        $product = new Product(
            'p1',
            'Laptop',
            Money::fromCents(250000),
            Weight::fromKilograms(2.5),
            1
        );

        self::assertSame('p1', $product->id());
        self::assertSame('Laptop', $product->name());
        self::assertSame(250000, $product->price()->amountInCents());
        self::assertSame(2500, $product->weight()->grams());
        self::assertSame(1, $product->quantity());
    }

    #[Test]
    public function itCalculatesTotalPrice(): void
    {
        $product = new Product(
            'p1',
            'Mouse',
            Money::fromCents(10000), // 100 PLN
            Weight::fromGrams(200),
            3
        );

        self::assertSame(30000, $product->totalPrice()->amountInCents()); // 300 PLN
    }

    #[Test]
    public function itCalculatesTotalWeight(): void
    {
        $product = new Product(
            'p1',
            'Mouse',
            Money::fromCents(10000),
            Weight::fromGrams(200),
            3
        );

        self::assertSame(600, $product->totalWeight()->grams()); // 600g
    }

    #[Test]
    public function itDefaultsQuantityToOne(): void
    {
        $product = new Product(
            'p1',
            'Laptop',
            Money::fromCents(250000),
            Weight::fromKilograms(2.5)
        );

        self::assertSame(1, $product->quantity());
        self::assertSame(250000, $product->totalPrice()->amountInCents());
    }
}
