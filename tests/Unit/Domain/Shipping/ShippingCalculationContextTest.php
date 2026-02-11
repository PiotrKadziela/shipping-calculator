<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shipping;

use App\Domain\Entity\Country;
use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\Event\ShippingRuleAppliedEvent;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use PHPUnit\Framework\Attributes\Test;
use App\Tests\Support\BaseTestCase;

final class ShippingCalculationContextTest extends BaseTestCase
{
    #[Test]
    public function it_creates_context_for_order(): void
    {
        $order = $this->createOrder();

        $context = ShippingCalculationContext::forOrder($order);

        self::assertSame($order, $context->order());
        self::assertTrue($context->currentCost()->isZero());
        self::assertEmpty($context->events());
        self::assertEmpty($context->appliedRules());
    }

    #[Test]
    public function it_creates_new_context_with_cost(): void
    {
        $order = $this->createOrder();
        $context = ShippingCalculationContext::forOrder($order);

        $newContext = $context->withCost(
            Money::fromCents(1000),
            'test_rule',
            'Test description'
        );

        // Original is unchanged (immutability)
        self::assertTrue($context->currentCost()->isZero());

        // New context has updated cost
        self::assertSame(1000, $newContext->currentCost()->amountInCents());
        self::assertContains('test_rule', $newContext->appliedRules());
        self::assertCount(1, $newContext->events());
    }

    #[Test]
    public function it_adds_cost(): void
    {
        $order = $this->createOrder();
        $context = ShippingCalculationContext::forOrder($order)
            ->withCost(Money::fromCents(1000), 'base', 'Base cost');

        $newContext = $context->withAddedCost(
            Money::fromCents(500),
            'surcharge',
            'Surcharge'
        );

        self::assertSame(1500, $newContext->currentCost()->amountInCents());
    }

    #[Test]
    public function it_tracks_free_shipping(): void
    {
        $order = $this->createOrder();
        $contextWithCost = ShippingCalculationContext::forOrder($order)
            ->withCost(Money::fromCents(1000), 'base', 'Base');
        $contextFree = ShippingCalculationContext::forOrder($order);

        self::assertFalse($contextWithCost->isFreeShipping());
        self::assertTrue($contextFree->isFreeShipping());
    }

    #[Test]
    public function it_checks_applied_rules(): void
    {
        $order = $this->createOrder();
        $context = ShippingCalculationContext::forOrder($order)
            ->withCost(Money::fromCents(1000), 'rule_1', 'First rule')
            ->withCost(Money::fromCents(500), 'rule_2', 'Second rule');

        self::assertTrue($context->hasAppliedRule('rule_1'));
        self::assertTrue($context->hasAppliedRule('rule_2'));
        self::assertFalse($context->hasAppliedRule('rule_3'));
    }

    #[Test]
    public function it_records_events_with_correct_data(): void
    {
        $order = $this->createOrder();
        $context = ShippingCalculationContext::forOrder($order)
            ->withCost(Money::fromCents(1000), 'base', 'Base rate')
            ->withCost(Money::fromCents(1500), 'surcharge', 'Weight surcharge');

        $events = $context->events();

        self::assertCount(2, $events);

        /** @var ShippingRuleAppliedEvent $firstEvent */
        $firstEvent = $events[0];
        self::assertSame('base', $firstEvent->ruleName());
        self::assertSame(0, $firstEvent->costBefore()->amountInCents());
        self::assertSame(1000, $firstEvent->costAfter()->amountInCents());

        /** @var ShippingRuleAppliedEvent $secondEvent */
        $secondEvent = $events[1];
        self::assertSame('surcharge', $secondEvent->ruleName());
        self::assertSame(1000, $secondEvent->costBefore()->amountInCents());
        self::assertSame(1500, $secondEvent->costAfter()->amountInCents());
    }

    private function createOrder(): Order
    {
        return Order::withExplicitValues(
            'order_1',
            [new Product('p1', 'Test', Money::fromCents(10000), Weight::fromKilograms(1))],
            Weight::fromKilograms(1),
            Money::fromCents(10000),
            $this->poland(),
            OrderDate::fromString('2024-01-15')
        );
    }
}

