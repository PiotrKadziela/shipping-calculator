<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Country;
use App\Domain\Repository\CountryRepositoryInterface;
use PDO;
use RuntimeException;

final class DbCountryRepository implements CountryRepositoryInterface
{
    /** @var array<string, Country|null> */
    private array $cacheByCode = [];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByCode(string $code): ?Country
    {
        $code = strtoupper(trim($code));

        if (array_key_exists($code, $this->cacheByCode)) {
            return $this->cacheByCode[$code];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, active FROM countries WHERE code = :code LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        if ($row === false) {
            $this->cacheByCode[$code] = null;
            return null;
        }

        $country = Country::create(
            (int) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            (bool) $row['active']
        );

        $this->cacheByCode[$code] = $country;

        return $country;
    }

    public function findAllActive(): array
    {
        $stmt = $this->pdo->query('SELECT id, code, name, active FROM countries WHERE active = 1 ORDER BY name');
        if ($stmt === false) {
            throw new RuntimeException('Failed to load active countries');
        }

        $countries = [];

        foreach ($stmt->fetchAll() as $row) {
            $country = Country::create(
                (int) $row['id'],
                (string) $row['code'],
                (string) $row['name'],
                (bool) $row['active']
            );

            $countries[] = $country;
            $this->cacheByCode[$country->code()] = $country;
        }

        return $countries;
    }
}
