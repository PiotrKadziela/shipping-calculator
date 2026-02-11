<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Application\Service\ShippingCostCalculator;
use App\Domain\Entity\Country;
use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\Shipping\Rule\BaseCountryRateRule;
use App\Domain\Shipping\Rule\FreeShippingRule;
use App\Domain\Shipping\Rule\FridayPromotionRule;
use App\Domain\Shipping\Rule\HalfPriceShippingRule;
use App\Domain\Shipping\Rule\WeightSurchargeRule;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use App\Tests\Support\BaseTestCase;
use App\Tests\Support\Shipping\Config\Repository\InMemoryBaseCountryRateConfigRepository;
use App\Tests\Support\Shipping\Config\Repository\InMemoryWeightSurchargeConfigRepository;
use App\Tests\Support\Shipping\Config\Repository\InMemoryFreeShippingConfigRepository;
use App\Tests\Support\Shipping\Config\Repository\InMemoryHalfPriceShippingConfigRepository;
use App\Tests\Support\Shipping\Config\Repository\InMemoryFridayPromotionConfigRepository;
use PHPUnit\Framework\Attributes\Test;

/**
 * Acceptance tests covering all business requirements.
 * They test the full calculation flow with all rules.
 */
final class ShippingCostCalculatorAcceptanceTest extends BaseTestCase
{
    private ShippingCostCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new ShippingCostCalculator([
            new BaseCountryRateRule(InMemoryBaseCountryRateConfigRepository::defaults()),
            new WeightSurchargeRule(InMemoryWeightSurchargeConfigRepository::defaults()),
            new FreeShippingRule(InMemoryFreeShippingConfigRepository::defaults($this)),
            new HalfPriceShippingRule(InMemoryHalfPriceShippingConfigRepository::defaults($this)),
            new FridayPromotionRule(InMemoryFridayPromotionConfigRepository::defaults()),
        ]);
    }

    // ==========================================
    // SCENARIOS: Base rates by country
    // ==========================================

    #[Test]
    public function scenario_basic_order_to_poland(): void
    {
        // Given: Order to Poland, weight 2kg, value 100 PLN, Monday
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 2.0,
            cartValuePln: 100.0,
            date: '2024-01-15' // Monday
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Cost = 10 PLN (base rate for Poland)
        self::assertSame(1000, $result->shippingCost()->amountInCents());
        self::assertSame('10.00 PLN', $result->shippingCost()->format());
    }

    #[Test]
    public function scenario_basic_order_to_germany(): void
    {
        // Given: Order to Germany, weight 2kg, value 100 PLN, Monday
        $order = $this->createOrder(
            country: $this->germany(),
            weightKg: 2.0,
            cartValuePln: 100.0,
            date: '2024-01-15'
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Cost = 20 PLN (base rate for Germany)
        self::assertSame(2000, $result->shippingCost()->amountInCents());
    }

    #[Test]
    public function scenario_basic_order_to_usa(): void
    {
        // Given: Order to USA, weight 2kg, value 100 PLN, Monday
        $order = $this->createOrder(
            country: $this->usa(),
            weightKg: 2.0,
            cartValuePln: 100.0,
            date: '2024-01-15'
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Cost = 50 PLN (base rate for USA)
        self::assertSame(5000, $result->shippingCost()->amountInCents());
    }

    #[Test]
    public function scenario_basic_order_to_france(): void
    {
        // Given: Order to France, weight 2kg, value 100 PLN, Monday
        $order = $this->createOrder(
            country: $this->country('FR'),
            weightKg: 2.0,
            cartValuePln: 100.0,
            date: '2024-01-15'
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Cost = 39.99 PLN (default rate)
        self::assertSame(3999, $result->shippingCost()->amountInCents());
    }

    // ==========================================
    // SCENARIOS: Weight surcharge
    // ==========================================

    #[Test]
    public function scenario_weight_surcharge_7_2_kg(): void
    {
        // Given: 7.2 kg package to Poland (example from requirements)
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 7.2,
            cartValuePln: 100.0,
            date: '2024-01-15'
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Cost = 10 PLN (base) + 9 PLN (3 started kg * 3 PLN) = 19 PLN
        self::assertSame(1900, $result->shippingCost()->amountInCents());
        self::assertContains('weight_surcharge', $result->appliedRules());
    }

    #[Test]
    public function scenario_no_weight_surcharge_at_5kg(): void
    {
        // Given: Package exactly 5 kg to Poland
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 5.0,
            cartValuePln: 100.0,
            date: '2024-01-15'
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Cost = 10 PLN (base only, no surcharge)
        self::assertSame(1000, $result->shippingCost()->amountInCents());
        self::assertNotContains('weight_surcharge', $result->appliedRules());
    }

    #[Test]
    public function scenario_weight_surcharge_just_above_limit(): void
    {
        // Given: Package 5.1 kg to Poland
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 5.1,
            cartValuePln: 100.0,
            date: '2024-01-15'
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Cost = 10 PLN (base) + 3 PLN (1 started kg) = 13 PLN
        self::assertSame(1300, $result->shippingCost()->amountInCents());
    }

    // ==========================================
    // SCENARIOS: Value promotion
    // ==========================================

    #[Test]
    public function scenario_free_shipping_for_high_value_order(): void
    {
        // Given: Order to Poland for 500 PLN
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 2.0,
            cartValuePln: 500.0,
            date: '2024-01-15'
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Free shipping
        self::assertTrue($result->isFreeShipping());
        self::assertSame(0, $result->shippingCost()->amountInCents());
        self::assertContains('free_shipping', $result->appliedRules());
    }

    #[Test]
    public function scenario_free_shipping_at_exactly_400_pln(): void
    {
        // Given: Order for exactly 400 PLN
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 2.0,
            cartValuePln: 400.0,
            date: '2024-01-15'
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Free shipping
        self::assertTrue($result->isFreeShipping());
    }

    #[Test]
    public function scenario_no_free_shipping_below_400_pln(): void
    {
        // Given: Order for 399.99 PLN
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 2.0,
            cartValuePln: 399.99,
            date: '2024-01-15'
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Regular price
        self::assertFalse($result->isFreeShipping());
        self::assertNotContains('free_shipping', $result->appliedRules());
    }

    #[Test]
    public function scenario_usa_gets_50_percent_instead_of_free(): void
    {
        // Given: Order to USA for 500 PLN
        $order = $this->createOrder(
            country: $this->usa(),
            weightKg: 2.0,
            cartValuePln: 500.0,
            date: '2024-01-15' // Monday
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: 50% discount instead of free shipping = 25 PLN
        self::assertFalse($result->isFreeShipping());
        self::assertSame(2500, $result->shippingCost()->amountInCents());
    }

    // ==========================================
    // SCENARIOS: Friday promotion
    // ==========================================

    #[Test]
    public function scenario_friday_50_percent_discount(): void
    {
        // Given: Order to Poland on Friday
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 2.0,
            cartValuePln: 100.0,
            date: '2024-01-19' // Friday
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: 50% discount = 5 PLN
        self::assertSame(500, $result->shippingCost()->amountInCents());
        self::assertContains('friday_promotion', $result->appliedRules());
    }

    #[Test]
    public function scenario_friday_no_discount_when_free_shipping(): void
    {
        // Given: Order for 500 PLN on Friday
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 2.0,
            cartValuePln: 500.0,
            date: '2024-01-19' // Friday
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Free shipping (Friday discount does not apply)
        self::assertTrue($result->isFreeShipping());
        self::assertNotContains('friday_promotion', $result->appliedRules());
    }

    #[Test]
    public function scenario_friday_stacks_with_usa_discount(): void
    {
        // Given: Order to USA for 500 PLN on Friday
        $order = $this->createOrder(
            country: $this->usa(),
            weightKg: 2.0,
            cartValuePln: 500.0,
            date: '2024-01-19' // Friday
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then:
        // 1. Base: 50 PLN
        // 2. USA promotion (50%): 25 PLN
        // 3. Friday promotion (50%): 12.50 PLN
        self::assertSame(1250, $result->shippingCost()->amountInCents());
        self::assertContains('half_price_shipping', $result->appliedRules());
        self::assertContains('friday_promotion', $result->appliedRules());
    }

    // ==========================================
    // SCENARIOS: Rule combinations
    // ==========================================

    #[Test]
    public function scenario_weight_surcharge_and_friday_discount(): void
    {
        // Given: Heavy package (7.2 kg) on Friday
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 7.2,
            cartValuePln: 100.0,
            date: '2024-01-19' // Friday
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then:
        // 1. Base: 10 PLN
        // 2. Weight (+9 PLN): 19 PLN
        // 3. Friday (50%): 9.50 PLN
        self::assertSame(950, $result->shippingCost()->amountInCents());
    }

    #[Test]
    public function scenario_heavy_order_with_free_shipping(): void
    {
        // Given: Heavy package (7.2 kg) for 500 PLN
        $order = $this->createOrder(
            country: $this->poland(),
            weightKg: 7.2,
            cartValuePln: 500.0,
            date: '2024-01-15' // Monday
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Free shipping (value promotion overrides weight surcharge)
        self::assertTrue($result->isFreeShipping());
    }

    #[Test]
    public function scenario_usa_heavy_order_on_friday(): void
    {
        // Given: Heavy package (7.2 kg) to USA for 500 PLN on Friday
        $order = $this->createOrder(
            country: $this->usa(),
            weightKg: 7.2,
            cartValuePln: 500.0,
            date: '2024-01-19' // Friday
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then:
        // 1. Base USA: 50 PLN
        // 2. Weight (+9 PLN): 59 PLN
        // 3. USA promotion (50%): 29.50 PLN
        // 4. Friday (50%): 14.75 PLN
        self::assertSame(1475, $result->shippingCost()->amountInCents());
    }

    // ==========================================
    // SCENARIOS: Rule order verification
    // ==========================================

    #[Test]
    public function scenario_rules_applied_in_correct_order(): void
    {
        // Given: Order with all possible rules
        $order = $this->createOrder(
            country: $this->usa(),
            weightKg: 7.2,
            cartValuePln: 500.0,
            date: '2024-01-19' // Friday
        );

        // When: Calculate shipping cost
        $result = $this->calculator->calculate($order);

        // Then: Rules are applied in the correct order
        $expectedOrder = [
            'base_country_rate',
            'weight_surcharge',
            'half_price_shipping',
            'friday_promotion',
        ];
        self::assertSame($expectedOrder, $result->appliedRules());
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function createOrder(
        Country $country,
        float $weightKg,
        float $cartValuePln,
        string $date
    ): Order {
        $products = [
            new Product(
                'product_1',
                'Test Product',
                Money::fromDecimal($cartValuePln),
                Weight::fromKilograms($weightKg)
            )
        ];

        return Order::withExplicitValues(
            uniqid('order_'),
            $products,
            Weight::fromKilograms($weightKg),
            Money::fromDecimal($cartValuePln),
            $country,
            OrderDate::fromString($date)
        );
    }
}

