<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use App\Domain\Event\ShippingCostCalculatedEvent;
use App\Domain\Event\ShippingRuleAppliedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that logs shipping calculation events.
 * Example of Event-Driven Architecture where components react to domain events.
 */
final class ShippingCalculationEventSubscriber implements EventSubscriberInterface
{
    /** @var list<array<string, mixed>> */
    private array $log = [];

    public static function getSubscribedEvents(): array
    {
        return [
            ShippingRuleAppliedEvent::class => 'onRuleApplied',
            ShippingCostCalculatedEvent::class => 'onCalculationCompleted',
        ];
    }

    public function onRuleApplied(ShippingRuleAppliedEvent $event): void
    {
        $this->log[] = [
            'type' => 'rule_applied',
            'orderId' => $event->orderId(),
            'rule' => $event->ruleName(),
            'costBefore' => $event->costBefore()->format(),
            'costAfter' => $event->costAfter()->format(),
            'description' => $event->description(),
            'timestamp' => $event->occurredAt()->format('Y-m-d H:i:s.u'),
        ];
    }

    public function onCalculationCompleted(ShippingCostCalculatedEvent $event): void
    {
        $this->log[] = [
            'type' => 'calculation_completed',
            'orderId' => $event->orderId(),
            'firstRuleCost' => $event->firstRuleCost()->format(),
            'finalCost' => $event->finalCost()->format(),
            'appliedRules' => $event->appliedRules(),
            'timestamp' => $event->occurredAt()->format('Y-m-d H:i:s.u'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLog(): array
    {
        return $this->log;
    }

    public function clearLog(): void
    {
        $this->log = [];
    }
}


