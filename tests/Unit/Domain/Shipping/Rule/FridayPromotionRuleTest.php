<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shipping\Rule;

use App\Domain\Entity\Country;
use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\Shipping\Rule\FridayPromotionRule;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use App\Tests\Support\BaseTestCase;
use App\Tests\Support\Shipping\Config\Repository\InMemoryFridayPromotionConfigRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class FridayPromotionRuleTest extends BaseTestCase
{
    private FridayPromotionRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new FridayPromotionRule(InMemoryFridayPromotionConfigRepository::defaults());
    }

    #[Test]
    public function itHasCorrectNameAndPriority(): void
    {
        self::assertSame('friday_promotion', $this->rule->getName());
        self::assertSame(400, $this->rule->getPriority());
    }

    #[Test]
    #[DataProvider('supportDataProvider')]
    public function itSupportsOnlyOnFridayWhenNotFree(string $date, int $currentCost, bool $expectedSupport): void
    {
        $context = $this->createContext($date, $currentCost);

        self::assertSame($expectedSupport, $this->rule->supports($context));
    }

    public static function supportDataProvider(): array
    {
        return [
            'Friday with cost' => ['2024-01-19', 1000, true],
            'Monday with cost' => ['2024-01-15', 1000, false],
            'Friday with free shipping' => ['2024-01-19', 0, false],
            'Saturday with cost' => ['2024-01-20', 1000, false],
            'Another Friday' => ['2024-01-26', 500, true],
        ];
    }

    #[Test]
    public function itApplies50PercentDiscount(): void
    {
        $context = $this->createContext('2024-01-19', 2000); // 20 PLN

        $result = $this->rule->apply($context);

        self::assertSame(1000, $result->currentCost()->amountInCents()); // 10 PLN
    }

    #[Test]
    public function itStacksWithUsaDiscount(): void
    {
        // Simulation: USA already has a 50% discount (25 PLN from 50 PLN)
        // Friday promotion adds another 50% = 12.50 PLN
        $context = $this->createContext('2024-01-19', 2500, $this->usa());

        $result = $this->rule->apply($context);

        // 2500 cents * 50% = 1250 cents = 12.50 PLN (rounded)
        self::assertSame(1250, $result->currentCost()->amountInCents());
    }

    #[Test]
    public function itRecordsEvent(): void
    {
        $context = $this->createContext('2024-01-19', 2000);

        $result = $this->rule->apply($context);

        // 2 events: test_base + friday_promotion
        self::assertCount(2, $result->events());
        self::assertContains('friday_promotion', $result->appliedRules());
    }

    private function createContext(
        string $date,
        int $currentCostCents,
        ?Country $country = null,
    ): ShippingCalculationContext {
        $order = Order::withExplicitValues(
            'order_1',
            [new Product('p1', 'Test', Money::fromCents(10000), Weight::fromKilograms(1))],
            Weight::fromKilograms(1),
            Money::fromCents(10000),
            $country ?? $this->poland(),
            OrderDate::fromString($date)
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
