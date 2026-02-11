<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\ShippingCostCalculator;
use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\Event\ShippingCostCalculatedEvent;
use App\Domain\Event\ShippingRuleAppliedEvent;
use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\Shipping\ShippingRuleInterface;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use App\Tests\Support\BaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ShippingCostCalculatorTest extends BaseTestCase
{
    #[Test]
    public function itAppliesRulesInPriorityOrder(): void
    {
        // Given: Rules with different priorities (provided in the wrong order)
        $lowPriorityRule = $this->createMockRule('low', 300, true, 3000);
        $highPriorityRule = $this->createMockRule('high', 100, true, 1000);
        $mediumPriorityRule = $this->createMockRule('medium', 200, true, 2000);

        $calculator = new ShippingCostCalculator([
            $lowPriorityRule,    // Priority 300
            $highPriorityRule,   // Priority 100
            $mediumPriorityRule, // Priority 200
        ]);

        $order = $this->createOrder();

        // When
        $result = $calculator->calculate($order);

        // Then: Rules should be applied in priority order
        self::assertSame(['high', 'medium', 'low'], $result->appliedRules());
    }

    #[Test]
    public function itSkipsUnsupportedRules(): void
    {
        // Given
        $supportedRule = $this->createMockRule('supported', 100, true, 1000);
        $unsupportedRule = $this->createMockRule('unsupported', 200, false, 0);

        $calculator = new ShippingCostCalculator([$supportedRule, $unsupportedRule]);
        $order = $this->createOrder();

        // When
        $result = $calculator->calculate($order);

        // Then
        self::assertContains('supported', $result->appliedRules());
        self::assertNotContains('unsupported', $result->appliedRules());
    }

    #[Test]
    public function itDispatchesEvents(): void
    {
        // Given
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::atLeast(2))
            ->method('dispatch')
            ->with(self::logicalOr(
                self::isInstanceOf(ShippingRuleAppliedEvent::class),
                self::isInstanceOf(ShippingCostCalculatedEvent::class)
            ));

        $rule = $this->createMockRule('test', 100, true, 1000);
        $calculator = new ShippingCostCalculator([$rule], $dispatcher);
        $order = $this->createOrder();

        // When
        $calculator->calculate($order);

        // Then: Expectations verified by mock
    }

    #[Test]
    public function itReportsFirstRuleCost(): void
    {
        // Given
        $events = [];
        $dispatcher = new class ($events) implements EventDispatcherInterface {
            /** @var object[] */
            private array $events;

            public function __construct(array &$events)
            {
                $this->events = &$events;
            }

            public function dispatch(object $event): object
            {
                $this->events[] = $event;

                return $event;
            }
        };

        $rule = $this->createMockRule('first_rule', 100, true, 1200);
        $calculator = new ShippingCostCalculator([$rule], $dispatcher);
        $order = $this->createOrder();

        // When
        $calculator->calculate($order);

        // Then
        $finalEvent = null;
        foreach ($events as $event) {
            if ($event instanceof ShippingCostCalculatedEvent) {
                $finalEvent = $event;
                break;
            }
        }

        self::assertNotNull($finalEvent);
        self::assertSame(1200, $finalEvent->firstRuleCost()->amountInCents());
    }

    #[Test]
    public function itReturnsZeroCostWhenNoRules(): void
    {
        // Given
        $calculator = new ShippingCostCalculator([]);
        $order = $this->createOrder();

        // When
        $result = $calculator->calculate($order);

        // Then
        self::assertTrue($result->shippingCost()->isZero());
        self::assertEmpty($result->appliedRules());
    }

    #[Test]
    public function itReturnsResultWithAllEvents(): void
    {
        // Given
        $rule1 = $this->createMockRule('rule1', 100, true, 1000);
        $rule2 = $this->createMockRule('rule2', 200, true, 1500);

        $calculator = new ShippingCostCalculator([$rule1, $rule2]);
        $order = $this->createOrder();

        // When
        $result = $calculator->calculate($order);

        // Then
        self::assertCount(2, $result->events());
        self::assertSame(1500, $result->shippingCost()->amountInCents());
    }

    private function createMockRule(string $name, int $priority, bool $supports, int $resultCostCents): ShippingRuleInterface
    {
        return new class ($name, $priority, $supports, $resultCostCents) implements ShippingRuleInterface {
            public function __construct(
                private readonly string $name,
                private readonly int $priority,
                private readonly bool $supports,
                private readonly int $resultCostCents,
            ) {}

            public function getName(): string
            {
                return $this->name;
            }

            public function getPriority(): int
            {
                return $this->priority;
            }

            public function supports(ShippingCalculationContext $context): bool
            {
                return $this->supports;
            }

            public function apply(ShippingCalculationContext $context): ShippingCalculationContext
            {
                return $context->withCost(
                    Money::fromCents($this->resultCostCents),
                    $this->name,
                    "Applied {$this->name}"
                );
            }
        };
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
