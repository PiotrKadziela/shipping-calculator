<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shipping\Rule;

use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\Entity\Country;
use App\Domain\Shipping\Rule\WeightSurchargeRule;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use App\Tests\Support\Shipping\Config\Repository\InMemoryWeightSurchargeConfigRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use App\Tests\Support\BaseTestCase;

final class WeightSurchargeRuleTest extends BaseTestCase
{
    private WeightSurchargeRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new WeightSurchargeRule(InMemoryWeightSurchargeConfigRepository::defaults());
    }

    #[Test]
    public function it_has_correct_name_and_priority(): void
    {
        self::assertSame('weight_surcharge', $this->rule->getName());
        self::assertSame(200, $this->rule->getPriority());
    }

    #[Test]
    #[DataProvider('supportDataProvider')]
    public function it_supports_only_when_weight_exceeds_limit(float $weightKg, bool $expectedSupport): void
    {
        $context = $this->createContext($weightKg);

        self::assertSame($expectedSupport, $this->rule->supports($context));
    }

    public static function supportDataProvider(): array
    {
        return [
            'exactly 5kg - no support' => [5.0, false],
            'below 5kg - no support' => [3.0, false],
            'above 5kg - supports' => [5.1, true],
            '7.2kg - supports' => [7.2, true],
            '10kg - supports' => [10.0, true],
        ];
    }

    #[Test]
    #[DataProvider('surchargeDataProvider')]
    public function it_calculates_correct_surcharge(float $weightKg, int $expectedSurchargeCents): void
    {
        $context = $this->createContext($weightKg, 1000); // Base cost 10 PLN

        $result = $this->rule->apply($context);

        // Expected = base cost + surcharge
        $expectedTotalCents = 1000 + $expectedSurchargeCents;
        self::assertSame($expectedTotalCents, $result->currentCost()->amountInCents());
    }

    public static function surchargeDataProvider(): array
    {
        return [
            // 7.2kg - 5kg = 2.2kg excess = 3 started kg = 9 PLN
            '7.2kg -> +9 PLN' => [7.2, 900],
            // 5.1kg - 5kg = 0.1kg excess = 1 started kg = 3 PLN
            '5.1kg -> +3 PLN' => [5.1, 300],
            // 6kg - 5kg = 1kg excess = 1 started kg = 3 PLN
            '6kg -> +3 PLN' => [6.0, 300],
            // 8kg - 5kg = 3kg excess = 3 started kg = 9 PLN
            '8kg -> +9 PLN' => [8.0, 900],
            // 10kg - 5kg = 5kg excess = 5 started kg = 15 PLN
            '10kg -> +15 PLN' => [10.0, 1500],
            // 15.5kg - 5kg = 10.5kg excess = 11 started kg = 33 PLN
            '15.5kg -> +33 PLN' => [15.5, 3300],
        ];
    }

    #[Test]
    public function it_records_event(): void
    {
        $context = $this->createContext(7.2, 1000);

        $result = $this->rule->apply($context);

        // 2 events: test_base + weight_surcharge
        self::assertCount(2, $result->events());
        self::assertContains('weight_surcharge', $result->appliedRules());
    }

    private function createContext(float $weightKg, int $currentCostCents = 0): ShippingCalculationContext
    {
        $order = Order::withExplicitValues(
            'order_1',
            [new Product('p1', 'Test', Money::fromCents(10000), Weight::fromKilograms($weightKg))],
            Weight::fromKilograms($weightKg),
            Money::fromCents(10000),
            $this->poland(),
            OrderDate::fromString('2024-01-15')
        );

        $context = ShippingCalculationContext::forOrder($order);

        if ($currentCostCents > 0) {
            $context = $context->withCost(
                Money::fromCents($currentCostCents),
                'test_base',
                'Test base cost'
            );
        }

        return $context;
    }
}


