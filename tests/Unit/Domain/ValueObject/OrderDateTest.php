<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\OrderDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrderDateTest extends TestCase
{
    #[Test]
    public function itCreatesFromDatetime(): void
    {
        $dateTime = new \DateTimeImmutable('2024-01-15');
        $orderDate = OrderDate::fromDateTime($dateTime);

        self::assertSame('2024-01-15', $orderDate->format());
    }

    #[Test]
    public function itCreatesFromString(): void
    {
        $orderDate = OrderDate::fromString('2024-01-15');

        self::assertSame('2024-01-15', $orderDate->format());
    }

    #[Test]
    public function itCreatesFromStringWithTime(): void
    {
        $orderDate = OrderDate::fromString('2024-01-15 14:30:00');

        self::assertSame('2024-01-15', $orderDate->format());
    }

    #[Test]
    public function itThrowsOnInvalidDateFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');

        OrderDate::fromString('15-01-2024');
    }

    #[Test]
    public function itCreatesNow(): void
    {
        $orderDate = OrderDate::now();

        self::assertSame(date('Y-m-d'), $orderDate->format());
    }

    #[Test]
    #[DataProvider('fridayDataProvider')]
    public function itChecksIfFriday(string $date, bool $expectedIsFriday, string $expectedDayName): void
    {
        $orderDate = OrderDate::fromString($date);

        self::assertSame($expectedIsFriday, $orderDate->isFriday());
        self::assertSame($expectedDayName, $orderDate->dayOfWeekName());
    }

    public static function fridayDataProvider(): array
    {
        return [
            'Friday 2024-01-19' => ['2024-01-19', true, 'Friday'],
            'Monday 2024-01-15' => ['2024-01-15', false, 'Monday'],
            'Saturday 2024-01-20' => ['2024-01-20', false, 'Saturday'],
            'Friday 2024-01-26' => ['2024-01-26', true, 'Friday'],
            'Wednesday 2024-01-17' => ['2024-01-17', false, 'Wednesday'],
        ];
    }

    #[Test]
    public function itReturnsDayOfWeek(): void
    {
        // Monday = 1, Friday = 5, Sunday = 7
        $monday = OrderDate::fromString('2024-01-15');
        $friday = OrderDate::fromString('2024-01-19');

        self::assertSame(1, $monday->dayOfWeek());
        self::assertSame(5, $friday->dayOfWeek());
    }

    #[Test]
    public function itChecksEquality(): void
    {
        $a = OrderDate::fromString('2024-01-15');
        $b = OrderDate::fromString('2024-01-15');
        $c = OrderDate::fromString('2024-01-16');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    #[Test]
    public function itReturnsDatetimeImmutable(): void
    {
        $orderDate = OrderDate::fromString('2024-01-15');

        self::assertInstanceOf(\DateTimeImmutable::class, $orderDate->toDateTime());
    }
}
