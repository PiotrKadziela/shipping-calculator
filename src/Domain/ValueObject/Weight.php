<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Value Object representing weight.
 * Stores the value in grams as int to avoid float precision issues.
 */
final readonly class Weight
{
    private const int GRAMS_PER_KILOGRAM = 1000;

    private function __construct(
        private int $grams,
    ) {
        if ($grams < 0) {
            throw new \InvalidArgumentException('Weight cannot be negative');
        }
    }

    public static function fromGrams(int $grams): self
    {
        return new self($grams);
    }

    public static function fromKilograms(float $kilograms): self
    {
        return new self((int) round($kilograms * self::GRAMS_PER_KILOGRAM));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function grams(): int
    {
        return $this->grams;
    }

    public function kilograms(): float
    {
        return $this->grams / self::GRAMS_PER_KILOGRAM;
    }

    /**
     * Returns the number of full kilograms (rounded up).
     */
    public function ceilingKilograms(): int
    {
        return (int) ceil($this->grams / self::GRAMS_PER_KILOGRAM);
    }

    /**
     * Returns the number of "started" kilograms above the given limit.
     */
    public function excessKilogramsAbove(Weight $limit): int
    {
        if ($this->grams <= $limit->grams) {
            return 0;
        }

        $excessGrams = $this->grams - $limit->grams;

        return (int) ceil($excessGrams / self::GRAMS_PER_KILOGRAM);
    }

    public function isGreaterThan(Weight $other): bool
    {
        return $this->grams > $other->grams;
    }

    public function isLessThanOrEqual(Weight $other): bool
    {
        return $this->grams <= $other->grams;
    }

    public function add(Weight $other): self
    {
        return new self($this->grams + $other->grams);
    }

    public function equals(Weight $other): bool
    {
        return $this->grams === $other->grams;
    }

    public function format(): string
    {
        if ($this->grams >= self::GRAMS_PER_KILOGRAM) {
            return sprintf('%.2f kg', $this->kilograms());
        }

        return sprintf('%d g', $this->grams);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
