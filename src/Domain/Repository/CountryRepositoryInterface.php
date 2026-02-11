<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Country;

interface CountryRepositoryInterface
{
    public function findByCode(string $code): ?Country;

    /**
     * @return Country[]
     */
    public function findAllActive(): array;
}
