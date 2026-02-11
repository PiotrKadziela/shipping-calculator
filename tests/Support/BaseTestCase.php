<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Entity\Country;
use App\Domain\Repository\CountryRepositoryInterface;
use App\Tests\Support\Repository\InMemoryCountryRepository;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected CountryRepositoryInterface $countryRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->countryRepository = new InMemoryCountryRepository();
    }

    public function country(string $code): Country
    {
        return $this->countryRepository->findByCode($code)
            ?? throw new \RuntimeException(sprintf('Country %s not found', $code));
    }

    public function poland(): Country
    {
        return $this->country('PL');
    }

    public function germany(): Country
    {
        return $this->country('DE');
    }

    public function usa(): Country
    {
        return $this->country('US');
    }

    public function france(): Country
    {
        return $this->country('FR');
    }

    public function unitedKingdom(): Country
    {
        return $this->country('GB');
    }
}
