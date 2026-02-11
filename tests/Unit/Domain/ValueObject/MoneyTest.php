<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[Test]
    public function it_creates_from_cents(): void
    {
        $money = Money::fromCents(1500);

        self::assertSame(1500, $money->amountInCents());
        self::assertSame(15.0, $money->amountAsDecimal());
        self::assertSame('PLN', $money->currency());
    }

    #[Test]
    public function it_creates_from_decimal(): void
    {
        $money = Money::fromDecimal(15.99);

        self::assertSame(1599, $money->amountInCents());
        self::assertSame(15.99, $money->amountAsDecimal());
    }

    #[Test]
    public function it_creates_zero(): void
    {
        $money = Money::zero();

        self::assertTrue($money->isZero());
        self::assertSame(0, $money->amountInCents());
    }

    #[Test]
    public function it_throws_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(-100);
    }

    #[Test]
    public function it_adds_money(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(500);

        $result = $a->add($b);

        self::assertSame(1500, $result->amountInCents());
    }

    #[Test]
    public function it_subtracts_money(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(300);

        $result = $a->subtract($b);

        self::assertSame(700, $result->amountInCents());
    }

    #[Test]
    public function it_returns_zero_when_subtracting_more_than_available(): void
    {
        $a = Money::fromCents(100);
        $b = Money::fromCents(500);

        $result = $a->subtract($b);

        self::assertSame(0, $result->amountInCents());
    }

    #[Test]
    public function it_multiplies_by_factor(): void
    {
        $money = Money::fromCents(1000);

        $result = $money->multiply(1.5);

        self::assertSame(1500, $result->amountInCents());
    }

    #[Test]
    public function it_calculates_percentage(): void
    {
        $money = Money::fromCents(1000);

        $result = $money->percentage(50);

        self::assertSame(500, $result->amountInCents());
    }

    #[Test]
    #[DataProvider('comparisonDataProvider')]
    public function it_compares_money(int $a, int $b, bool $isGreater, bool $isGreaterOrEqual, bool $isLess): void
    {
        $moneyA = Money::fromCents($a);
        $moneyB = Money::fromCents($b);

        self::assertSame($isGreater, $moneyA->isGreaterThan($moneyB));
        self::assertSame($isGreaterOrEqual, $moneyA->isGreaterThanOrEqual($moneyB));
        self::assertSame($isLess, $moneyA->isLessThan($moneyB));
    }

    public static function comparisonDataProvider(): array
    {
        return [
            'greater' => [1000, 500, true, true, false],
            'equal' => [1000, 1000, false, true, false],
            'less' => [500, 1000, false, false, true],
        ];
    }

    #[Test]
    public function it_checks_equality(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(1000);
        $c = Money::fromCents(500);

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    #[Test]
    public function it_throws_on_different_currencies(): void
    {
        $pln = Money::fromCents(100, 'PLN');
        $eur = Money::fromCents(100, 'EUR');

        $this->expectException(InvalidArgumentException::class);

        $pln->add($eur);
    }

    #[Test]
    public function it_formats_correctly(): void
    {
        $money = Money::fromCents(1599);

        self::assertSame('15.99 PLN', $money->format());
        self::assertSame('15.99 PLN', (string) $money);
    }

    #[Test]
    public function it_handles_rounding_correctly(): void
    {
        // Test case: 39.99 PLN
        $money = Money::fromDecimal(39.99);

        self::assertSame(3999, $money->amountInCents());
        self::assertSame('39.99 PLN', $money->format());
    }
}

