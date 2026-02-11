<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shipping\Rule;

use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\Entity\Country;
use App\Domain\Shipping\Rule\BaseCountryRateRule;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use App\Tests\Support\Shipping\Config\Repository\InMemoryBaseCountryRateConfigRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use App\Tests\Support\BaseTestCase;

final class BaseCountryRateRuleTest extends BaseTestCase
{
    private BaseCountryRateRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new BaseCountryRateRule(InMemoryBaseCountryRateConfigRepository::defaults());
    }

    #[Test]
    public function it_has_correct_name_and_priority(): void
    {
        self::assertSame('base_country_rate', $this->rule->getName());
        self::assertSame(100, $this->rule->getPriority());
    }

    #[Test]
    public function it_always_supports(): void
    {
        $context = $this->createContext($this->poland());

        self::assertTrue($this->rule->supports($context));
    }

    #[Test]
    #[DataProvider('countryRatesDataProvider')]
    public function it_applies_correct_rate_for_country(Country $country, int $expectedCents): void
    {
        $context = $this->createContext($country);

        $result = $this->rule->apply($context);

        self::assertSame($expectedCents, $result->currentCost()->amountInCents());
    }

    public static function countryRatesDataProvider(): array
    {
        $repo = new \App\Tests\Support\Repository\InMemoryCountryRepository();
        
        return [
            'Poland - 10 PLN' => [$repo->poland(), 1000],
            'Germany - 20 PLN' => [$repo->germany(), 2000],
            'USA - 50 PLN' => [$repo->usa(), 5000],
            'France - 39.99 PLN (default)' => [$repo->findByCode('FR'), 3999],
            'UK - 39.99 PLN (default)' => [$repo->findByCode('GB'), 3999],
        ];
    }

    #[Test]
    public function it_records_event(): void
    {
        $context = $this->createContext($this->poland());

        $result = $this->rule->apply($context);

        self::assertCount(1, $result->events());
        self::assertContains('base_country_rate', $result->appliedRules());
    }

    private function createContext(Country $country): ShippingCalculationContext
    {
        $order = Order::withExplicitValues(
            'order_1',
            [new Product('p1', 'Test', Money::fromCents(10000), Weight::fromKilograms(1))],
            Weight::fromKilograms(1),
            Money::fromCents(10000),
            $country,
            OrderDate::fromString('2024-01-15')
        );

        return ShippingCalculationContext::forOrder($order);
    }
}

