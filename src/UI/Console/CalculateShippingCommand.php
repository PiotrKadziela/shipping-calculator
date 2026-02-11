<?php

declare(strict_types=1);

namespace App\UI\Console;

use App\Application\Service\ShippingCostCalculator;
use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\Event\ShippingRuleAppliedEvent;
use App\Domain\Repository\CountryRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderDate;
use App\Domain\ValueObject\Weight;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-shipping',
    description: 'Calculates shipping cost for an order'
)]
final class CalculateShippingCommand extends Command
{
    public function __construct(
        private readonly ShippingCostCalculator $calculator,
        private readonly CountryRepositoryInterface $countryRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'country',
                'c',
                InputOption::VALUE_REQUIRED,
                'Delivery country code (ISO 3166-1 alpha-2, e.g., PL, DE, US)',
                'PL'
            )
            ->addOption(
                'weight',
                'w',
                InputOption::VALUE_REQUIRED,
                'Total weight in kilograms (e.g., 7.2)',
                '1.0'
            )
            ->addOption(
                'value',
                'a',
                InputOption::VALUE_REQUIRED,
                'Cart value in PLN (e.g., 450.00)',
                '100.00'
            )
            ->addOption(
                'date',
                'd',
                InputOption::VALUE_REQUIRED,
                'Order date (Y-m-d format, e.g., 2024-01-19)',
                date('Y-m-d')
            )
            ->addOption(
                'products',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Products in format "name:price:weight:quantity" (e.g., "Laptop:2500:2.5:1")',
                []
            )
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command calculates shipping cost for an order.

                <info>Examples:</info>

                  Basic usage with explicit values:
                    <info>%command.full_name% --country=PL --weight=3.5 --value=250</info>

                  Order to USA with heavy package:
                    <info>%command.full_name% --country=US --weight=7.2 --value=100</info>

                  Free shipping (cart >= 400 PLN):
                    <info>%command.full_name% --country=PL --weight=2 --value=500</info>

                  Friday promotion (use a Friday date):
                    <info>%command.full_name% --country=DE --weight=3 --value=100 --date=2024-01-19</info>

                  With products:
                    <info>%command.full_name% --products="Laptop:2500:2.5:1" --products="Mouse:100:0.2:2" --country=PL --date=2024-01-15</info>

                <info>Options shortcuts:</info>
                  -c country, -w weight, -a value (amount), -d date, -p products

                <info>Business Rules:</info>
                  1. Base rates: PL=10PLN, DE=20PLN, US=50PLN, other=39.99PLN
                  2. Weight surcharge: +3PLN per started kg above 5kg
                  3. Value promotion: cart>=400PLN = free shipping (USA: 50% off instead)
                  4. Friday promotion: 50% off (stacks with USA discount)
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $order = $this->createOrder($input);
            $result = $this->calculator->calculate($order);

            $this->displayOrderDetails($io, $order);
            $this->displayCalculationSteps($io, $result->events());
            $this->displayResult($io, $result);

            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function createOrder(InputInterface $input): Order
    {
        $countryCode = $input->getOption('country');
        $weightKg = (float) $input->getOption('weight');
        $valuePln = (float) $input->getOption('value');
        $dateString = $input->getOption('date');
        $productStrings = $input->getOption('products');

        $country = $this->countryRepository->findByCode($countryCode);
        if (null === $country) {
            throw new \InvalidArgumentException(sprintf('Country with code "%s" not found', $countryCode));
        }

        $orderDate = OrderDate::fromString($dateString);

        if (!empty($productStrings)) {
            $products = $this->parseProducts($productStrings);

            return Order::create(
                uniqid('order_'),
                $products,
                $country,
                $orderDate
            );
        }

        // Create dummy product for explicit weight/value
        $products = [
            new Product(
                'product_1',
                'Order items',
                Money::fromDecimal($valuePln),
                Weight::fromKilograms($weightKg)
            ),
        ];

        return Order::withExplicitValues(
            uniqid('order_'),
            $products,
            Weight::fromKilograms($weightKg),
            Money::fromDecimal($valuePln),
            $country,
            $orderDate
        );
    }

    /**
     * @param string[] $productStrings
     *
     * @return Product[]
     */
    private function parseProducts(array $productStrings): array
    {
        $products = [];

        foreach ($productStrings as $index => $productString) {
            $parts = explode(':', $productString);

            if (count($parts) < 3) {
                throw new \InvalidArgumentException(sprintf('Invalid product format: "%s". Expected "name:price:weight[:quantity]"', $productString));
            }

            $name = $parts[0];
            $price = (float) $parts[1];
            $weight = (float) $parts[2];
            $quantity = isset($parts[3]) ? (int) $parts[3] : 1;

            $products[] = new Product(
                sprintf('product_%d', $index + 1),
                $name,
                Money::fromDecimal($price),
                Weight::fromKilograms($weight),
                $quantity
            );
        }

        return $products;
    }

    private function displayOrderDetails(SymfonyStyle $io, Order $order): void
    {
        $io->title('📦 Shipping Cost Calculator');

        $io->section('Order Details');
        $io->table(
            ['Property', 'Value'],
            [
                ['Order ID', $order->id()],
                ['Delivery Country', sprintf('%s (%s)', $order->deliveryCountry()->name(), $order->deliveryCountry()->code())],
                ['Total Weight', $order->totalWeight()->format()],
                ['Cart Value', $order->cartValue()->format()],
                ['Order Date', sprintf('%s (%s)', $order->orderDate()->format(), $order->orderDate()->dayOfWeekName())],
                ['Products Count', (string) $order->productCount()],
            ]
        );

        if (!empty($order->products())) {
            $io->section('Products');
            $productRows = [];
            foreach ($order->products() as $product) {
                $productRows[] = [
                    $product->name(),
                    $product->price()->format(),
                    $product->weight()->format(),
                    (string) $product->quantity(),
                    $product->totalPrice()->format(),
                ];
            }
            $io->table(
                ['Name', 'Unit Price', 'Unit Weight', 'Qty', 'Total Price'],
                $productRows
            );
        }
    }

    /**
     * @param object[] $events
     */
    private function displayCalculationSteps(SymfonyStyle $io, array $events): void
    {
        $io->section('Calculation Steps');

        $steps = [];
        foreach ($events as $event) {
            if ($event instanceof ShippingRuleAppliedEvent) {
                $steps[] = [
                    $event->ruleName(),
                    $event->costBefore()->format(),
                    $event->costAfter()->format(),
                    $event->description(),
                ];
            }
        }

        if (!empty($steps)) {
            $io->table(
                ['Rule', 'Cost Before', 'Cost After', 'Description'],
                $steps
            );
        }
    }

    private function displayResult(SymfonyStyle $io, \App\Application\Service\ShippingCalculationResult $result): void
    {
        $io->section('Result');

        if ($result->isFreeShipping()) {
            $io->success('🎉 FREE SHIPPING!');
        } else {
            $io->success(sprintf('💰 Shipping Cost: %s', $result->shippingCost()->format()));
        }

        $io->text(sprintf('Applied rules: %s', implode(' → ', $result->appliedRules())));
    }
}
