<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shipping\Rule;

use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\Entity\Country;
use App\Domain\Shipping\Rule\FreeShippingRule;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use App\Tests\Support\Shipping\Config\Repository\InMemoryFreeShippingConfigRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use App\Tests\Support\BaseTestCase;

final class FreeShippingRuleTest extends BaseTestCase
{
    private FreeShippingRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new FreeShippingRule(InMemoryFreeShippingConfigRepository::defaults($this));
    }

    #[Test]
    public function it_has_correct_name_and_priority(): void
    {
        self::assertSame('free_shipping', $this->rule->getName());
        self::assertSame(300, $this->rule->getPriority());
    }

    #[Test]
    #[DataProvider('supportDataProvider')]
    public function it_supports_only_when_cart_value_meets_threshold(int $cartValueCents, bool $expectedSupport): void
    {
        $context = $this->createContext($cartValueCents, $this->poland());

        self::assertSame($expectedSupport, $this->rule->supports($context));
    }

    public static function supportDataProvider(): array
    {
        return [
            'below 400 PLN' => [39900, false],
            'exactly 400 PLN' => [40000, true],
            'above 400 PLN' => [50000, true],
        ];
    }

    #[Test]
    public function it_grants_free_shipping(): void
    {
        $context = $this->createContext(50000, $this->poland(), 2000);

        $result = $this->rule->apply($context);

        self::assertTrue($result->currentCost()->isZero());
    }

    #[Test]
    public function it_grants_free_shipping_for_germany(): void
    {
        $context = $this->createContext(45000, $this->germany(), 2000);

        $result = $this->rule->apply($context);

        self::assertTrue($result->currentCost()->isZero());
    }

    #[Test]
    public function it_records_event(): void
    {
        $context = $this->createContext(50000, $this->poland(), 1000);

        $result = $this->rule->apply($context);

        self::assertCount(2, $result->events());
        self::assertContains('free_shipping', $result->appliedRules());
    }

    private function createContext(int $cartValueCents, Country $country, int $currentCostCents = 0): ShippingCalculationContext
    {
        $order = Order::withExplicitValues(
            'order_1',
            [new Product('p1', 'Test', Money::fromCents($cartValueCents), Weight::fromKilograms(1))],
            Weight::fromKilograms(1),
            Money::fromCents($cartValueCents),
            $country,
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
