# Shipping Calculator

Symfony module for calculating order shipping costs based on overlapping business rules.

## Architecture

This project uses **Domain-Driven Design (DDD)** and **Event-Driven Architecture**.

### Directory structure

```
src/
├── Domain/                          # Domain layer (core business logic)
│   ├── Entity/                      # Domain entities (mutable, with ID)
│   │   ├── Order.php                # Aggregate Root - order
│   │   ├── Product.php              # Product in an order
│   │   └── Country.php              # Country from database
│   ├── ValueObject/                 # Value Objects (immutable)
│   │   ├── Money.php                # Money representation (cents as int)
│   │   ├── Weight.php               # Weight representation (grams as int)
│   │   └── OrderDate.php            # Order date
│   ├── Event/                       # Domain Events
│   │   ├── DomainEventInterface.php
│   │   ├── AbstractDomainEvent.php
│   │   ├── ShippingCostCalculatedEvent.php
│   │   └── ShippingRuleAppliedEvent.php
│   └── Shipping/                    # Shipping calculation logic
│       ├── ShippingRuleInterface.php    # Rules interface (Open/Closed)
│       ├── ShippingCalculationContext.php
│       └── Rule/                    # Rule implementations
│           ├── BaseCountryRateRule.php
│           ├── WeightSurchargeRule.php
│           ├── FreeShippingRule.php
│           ├── HalfPriceShippingRule.php
│           └── FridayPromotionRule.php
├── Application/                     # Application layer
│   └── Service/
│       ├── ShippingCostCalculator.php   # Main calculator service
│       └── ShippingCalculationResult.php
├── Infrastructure/                  # Infrastructure layer
│   └── EventListener/
│       └── ShippingCalculationEventSubscriber.php
├── UI/                              # Presentation layer
│   └── Console/
│       └── CalculateShippingCommand.php # Symfony command
└── Kernel.php                       # DI wiring
```

### Design patterns

1. **Chain of Responsibility** - rules are processed sequentially
2. **Strategy Pattern** - each rule implements `ShippingRuleInterface`
3. **Immutable Objects** - Value Objects and Context are immutable
4. **Factory Method** - creating `Order` instances
5. **Event-Driven Architecture** - domain events emitted during calculation

### Data flow

```
Order -> ShippingCalculationContext -> [Rules Pipeline] -> ShippingCalculationResult
                                            |
                                            +-- Domain Events (emitted)
```

## Business rules

### Calculation order

1. **Base rate** (priority: 100)
2. **Weight surcharge** (priority: 200)
3. **Free shipping** (priority: 300)
4. **Half-price shipping** (priority: 305)
5. **Friday promotion** (priority: 400)

### Rule details

| Rule | Description |
|------|-------------|
| Base Country Rate | PL: 10 PLN, DE: 20 PLN, US: 50 PLN, other: 39.99 PLN |
| Weight Surcharge | > 5kg: +3 PLN per started kg |
| Free Shipping | >= 400 PLN: free shipping (non-US) |
| Half-Price Shipping | >= 400 PLN: 50% discount (US only) |
| Friday Promotion | Fridays: -50% (not when already free) |

## How to add a new rule

### Step 1: Create a rule class

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Rule;

use App\Domain\Shipping\ShippingCalculationContext;
use App\Domain\Shipping\ShippingRuleInterface;
use App\Domain\Shipping\Config\BulkyItemConfig;
use App\Domain\Shipping\Config\Repository\BulkyItemConfigRepositoryInterface;
use App\Domain\Entity\Order;
use App\Domain\ValueObject\Money;

final class BulkyItemSurchargeRule implements ShippingRuleInterface
{
    private const string NAME = 'bulky_item_surcharge';
    private ?BulkyItemConfig $cachedConfig = null;

    public function __construct(
        private readonly BulkyItemConfigRepositoryInterface $configRepository,
        private readonly int $priority = 250
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function supports(ShippingCalculationContext $context): bool
    {
        // Your logic to determine whether this rule applies
        return $this->hasBulkyItems($context->order());
    }

    public function apply(ShippingCalculationContext $context): ShippingCalculationContext
    {
        $config = $this->getConfig();
        $surcharge = $config->surchargeAmount();

        return $context->withAddedCost(
            $surcharge,
            self::NAME,
            'Bulky item surcharge: +20 PLN'
        );
    }

    private function hasBulkyItems(Order $order): bool
    {
        // Implementation for checking bulky items
    }

    private function getConfig(): BulkyItemConfig
    {
        return $this->cachedConfig ??= $this->configRepository->load();
    }
}
```

### Step 1b: Register the rule in the pipeline

Rules are created by `ShippingRuleFactory`, so new rules must be added there.

1) Add the rule to the factory:

```php
// src/Infrastructure/Shipping/ShippingRuleFactory.php
return [
    new BaseCountryRateRule(...),
    new WeightSurchargeRule(...),
    new BulkyItemSurchargeRule(/* dependencies */),
    new FreeShippingRule(...),
    // ...
];
```

2) If the rule needs configuration, create a config VO + repository and add DI wiring:

```yaml
# config/services.yaml
App\Domain\Shipping\Config\Repository\BulkyItemConfigRepositoryInterface:
    alias: App\Infrastructure\Shipping\Config\Repository\DbBulkyItemConfigRepository
```

This project does not autowire rule classes directly; the pipeline is defined in the factory.

### Step 1c: Add configuration in the database

If the rule is configurable, extend the database schema and seeds:

1) Add a table (or columns) in the migration:

```sql
-- docker/mysql/init/01_migration.sql
CREATE TABLE bulky_item_configs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    config_id BIGINT UNSIGNED NOT NULL,
    priority INT NOT NULL DEFAULT 250,
    is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    surcharge_amount DECIMAL(12, 2) NOT NULL,
    currency_code CHAR(3) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_config (config_id),
    CONSTRAINT fk_bulky_item_config
        FOREIGN KEY (config_id) REFERENCES shipping_configs(id) ON DELETE CASCADE,
    CONSTRAINT fk_bulky_item_currency
        FOREIGN KEY (currency_code) REFERENCES currencies(code)
) ENGINE=InnoDB;
```

2) Add seed data:

```sql
-- docker/mysql/init/03_seed_rules.sql
INSERT INTO bulky_item_configs (config_id, priority, is_enabled, surcharge_amount, currency_code)
VALUES (@config_id, 250, TRUE, 20.00, 'PLN');
```

3) Update `ShippingRuleFactory` priority loading if the rule participates in ordering.

### Step 2: Add tests

```php
#[Test]
public function it_applies_bulky_item_surcharge(): void
{
    // Given, When, Then...
}
```

## Running the project

### Requirements

- Docker
- Make

### Installation

```bash
# Build the image
make build

# Install dependencies
make install
```

### Run the calculator

```bash
# Basic usage
make calculate -- --country=PL --weight=3.5 --value=250

# Order to USA with a heavy package
make calculate -- --country=US --weight=7.2 --value=100

# Free shipping (cart >= 400 PLN)
make calculate -- --country=PL --weight=2 --value=500

# Friday promotion
make calculate -- --country=DE --weight=3 --value=100 --date=2024-01-19

# With products
make calculate -- --products="Laptop:2500:2.5:1" --products="Mouse:100:0.2:2" --country=PL

# Short flags: -c country, -w weight, -a value (amount), -d date, -p products
```

### Run directly inside the container

```bash
# Enter the container
make shell

# Run the command
php bin/console app:calculate-shipping --country=PL --weight=7.2 --value=100
```

## Running tests

```bash
# All tests
make test

# Unit tests only
make test-unit

# Acceptance tests only
make test-acceptance
```

### Test coverage

- **Value Objects**: `Money`, `Weight`, `OrderDate`
- **Business rules**: each rule has dedicated unit tests
- **Acceptance tests**: all business scenarios

## Code analysis

```bash
# PHPStan (static analysis)
make analyze

# PHP CS Fixer (formatting)
make cs-fix
```

## Sample output

```
Shipping Cost Calculator
========================

Order Details
-------------
 ------------ ---------------------------------------------- 
  Property     Value                                         
 ------------ ---------------------------------------------- 
  Order ID     order_65a12345                                
  Country      Poland (PL)                                   
  Weight       7.20 kg                                       
  Cart Value   100.00 PLN                                    
  Order Date   2024-01-19 (Friday)                           
 ------------ ---------------------------------------------- 

Calculation Steps
-----------------
 ------------------- ------------- ------------ --------------------------------- 
  Rule                Cost Before   Cost After   Description                       
 ------------------- ------------- ------------ --------------------------------- 
  base_country_rate   0.00 PLN      10.00 PLN    Base shipping rate for Poland    
  weight_surcharge    10.00 PLN     19.00 PLN    Weight surcharge: 3 excess kg(s) 
  friday_promotion    19.00 PLN     9.50 PLN     Friday promotion: 50% discount   
 ------------------- ------------- ------------ --------------------------------- 

Result
------
 [OK] Shipping Cost: 9.50 PLN

Applied rules: base_country_rate -> weight_surcharge -> friday_promotion
```

## Technical decisions

### Why amounts in cents (int)?

Floating-point operations can lead to precision issues:
```php
0.1 + 0.2 === 0.3 // false!
```

Using cents as int removes this problem:
```php
10 + 20 === 30 // always true
```

### Why immutable Value Objects?

- Thread safety
- Predictability (no side effects)
- Easier testing
- DDD alignment

### Why Event-Driven Architecture?

- Loose coupling between components
- Ability to add reactions without modifying core
- Auditing and logging for free
- Ready for future asynchronicity

## License

Proprietary - recruitment task


