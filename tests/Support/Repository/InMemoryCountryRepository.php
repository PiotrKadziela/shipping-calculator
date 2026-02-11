<?php

declare(strict_types=1);

namespace App\Tests\Support\Repository;

use App\Domain\Entity\Country;
use App\Domain\Repository\CountryRepositoryInterface;

final class InMemoryCountryRepository implements CountryRepositoryInterface
{
    /** @var array<string, Country> */
    private array $countries = [];

    public function __construct()
    {
        $this->seed();
    }

    public function findByCode(string $code): ?Country
    {
        $code = strtoupper(trim($code));

        return $this->countries[$code] ?? null;
    }

    public function findAllActive(): array
    {
        return array_filter($this->countries, fn(Country $c) => $c->active());
    }

    public function add(Country $country): void
    {
        $this->countries[$country->code()] = $country;
    }

    public function poland(): Country
    {
        return $this->findByCode('PL') ?? throw new \RuntimeException('Poland not found');
    }

    public function germany(): Country
    {
        return $this->findByCode('DE') ?? throw new \RuntimeException('Germany not found');
    }

    public function usa(): Country
    {
        return $this->findByCode('US') ?? throw new \RuntimeException('USA not found');
    }

    private function seed(): void
    {
        $this->add(Country::create(1, 'PL', 'Poland', true));
        $this->add(Country::create(2, 'DE', 'Germany', true));
        $this->add(Country::create(3, 'US', 'United States', true));
        $this->add(Country::create(4, 'FR', 'France', true));
        $this->add(Country::create(5, 'GB', 'United Kingdom', true));
    }
}
