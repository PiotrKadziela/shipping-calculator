<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[Test]
    public function itCreatesFromCents(): void
    {
        $money = Money::fromCents(1500);

        self::assertSame(1500, $money->amountInCents());
        self::assertSame(15.0, $money->amountAsDecimal());
        self::assertSame('PLN', $money->currency());
    }

    #[Test]
    public function itCreatesFromDecimal(): void
    {
        $money = Money::fromDecimal(15.99);

        self::assertSame(1599, $money->amountInCents());
        self::assertSame(15.99, $money->amountAsDecimal());
    }

    #[Test]
    public function itCreatesZero(): void
    {
        $money = Money::zero();

        self::assertTrue($money->isZero());
        self::assertSame(0, $money->amountInCents());
    }

    #[Test]
    public function itThrowsOnNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::fromCents(-100);
    }

    #[Test]
    public function itAddsMoney(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(500);

        $result = $a->add($b);

        self::assertSame(1500, $result->amountInCents());
    }

    #[Test]
    public function itSubtractsMoney(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(300);

        $result = $a->subtract($b);

        self::assertSame(700, $result->amountInCents());
    }

    #[Test]
    public function itReturnsZeroWhenSubtractingMoreThanAvailable(): void
    {
        $a = Money::fromCents(100);
        $b = Money::fromCents(500);

        $result = $a->subtract($b);

        self::assertSame(0, $result->amountInCents());
    }

    #[Test]
    public function itMultipliesByFactor(): void
    {
        $money = Money::fromCents(1000);

        $result = $money->multiply(1.5);

        self::assertSame(1500, $result->amountInCents());
    }

    #[Test]
    public function itCalculatesPercentage(): void
    {
        $money = Money::fromCents(1000);

        $result = $money->percentage(50);

        self::assertSame(500, $result->amountInCents());
    }

    #[Test]
    #[DataProvider('comparisonDataProvider')]
    public function itComparesMoney(int $a, int $b, bool $isGreater, bool $isGreaterOrEqual, bool $isLess): void
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
    public function itChecksEquality(): void
    {
        $a = Money::fromCents(1000);
        $b = Money::fromCents(1000);
        $c = Money::fromCents(500);

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    #[Test]
    public function itThrowsOnDifferentCurrencies(): void
    {
        $pln = Money::fromCents(100, 'PLN');
        $eur = Money::fromCents(100, 'EUR');

        $this->expectException(\InvalidArgumentException::class);

        $pln->add($eur);
    }

    #[Test]
    public function itFormatsCorrectly(): void
    {
        $money = Money::fromCents(1599);

        self::assertSame('15.99 PLN', $money->format());
        self::assertSame('15.99 PLN', (string) $money);
    }

    #[Test]
    public function itHandlesRoundingCorrectly(): void
    {
        // Test case: 39.99 PLN
        $money = Money::fromDecimal(39.99);

        self::assertSame(3999, $money->amountInCents());
        self::assertSame('39.99 PLN', $money->format());
    }
}
