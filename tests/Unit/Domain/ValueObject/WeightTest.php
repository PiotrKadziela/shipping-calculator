<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Weight;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WeightTest extends TestCase
{
    #[Test]
    public function itCreatesFromGrams(): void
    {
        $weight = Weight::fromGrams(5000);

        self::assertSame(5000, $weight->grams());
        self::assertSame(5.0, $weight->kilograms());
    }

    #[Test]
    public function itCreatesFromKilograms(): void
    {
        $weight = Weight::fromKilograms(7.2);

        self::assertSame(7200, $weight->grams());
        self::assertSame(7.2, $weight->kilograms());
    }

    #[Test]
    public function itCreatesZero(): void
    {
        $weight = Weight::zero();

        self::assertSame(0, $weight->grams());
    }

    #[Test]
    public function itThrowsOnNegativeWeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Weight::fromGrams(-100);
    }

    #[Test]
    public function itCalculatesCeilingKilograms(): void
    {
        // 7200g = 7.2kg -> ceil = 8kg
        $weight = Weight::fromGrams(7200);

        self::assertSame(8, $weight->ceilingKilograms());
    }

    #[Test]
    #[DataProvider('excessKilogramsDataProvider')]
    public function itCalculatesExcessKilogramsAboveLimit(int $grams, int $limitGrams, int $expectedExcess): void
    {
        $weight = Weight::fromGrams($grams);
        $limit = Weight::fromGrams($limitGrams);

        self::assertSame($expectedExcess, $weight->excessKilogramsAbove($limit));
    }

    public static function excessKilogramsDataProvider(): array
    {
        return [
            'exactly at limit' => [5000, 5000, 0],
            'below limit' => [3000, 5000, 0],
            '7.2kg above 5kg' => [7200, 5000, 3], // 2.2kg excess = 3 started kg
            '5.1kg above 5kg' => [5100, 5000, 1], // 0.1kg excess = 1 started kg
            '6kg above 5kg' => [6000, 5000, 1],   // 1kg excess = 1 started kg
            '10kg above 5kg' => [10000, 5000, 5], // 5kg excess = 5 started kg
        ];
    }

    #[Test]
    public function itComparesWeights(): void
    {
        $a = Weight::fromKilograms(5);
        $b = Weight::fromKilograms(3);
        $c = Weight::fromKilograms(5);

        self::assertTrue($a->isGreaterThan($b));
        self::assertFalse($b->isGreaterThan($a));
        self::assertTrue($b->isLessThanOrEqual($a));
        self::assertTrue($c->isLessThanOrEqual($a));
    }

    #[Test]
    public function itAddsWeights(): void
    {
        $a = Weight::fromKilograms(2);
        $b = Weight::fromKilograms(3);

        $result = $a->add($b);

        self::assertSame(5000, $result->grams());
    }

    #[Test]
    public function itFormatsCorrectly(): void
    {
        $light = Weight::fromGrams(500);
        $heavy = Weight::fromKilograms(7.2);

        self::assertSame('500 g', $light->format());
        self::assertSame('7.20 kg', $heavy->format());
    }
}
