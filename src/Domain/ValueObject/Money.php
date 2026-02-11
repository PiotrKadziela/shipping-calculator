<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Value Object representing a monetary amount.
 * Stores the value in cents as int to avoid float precision issues.
 */
final readonly class Money
{
    private function __construct(
        private int $amountInCents,
        private string $currency = 'PLN'
    ) {
        if ($amountInCents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    public static function fromCents(int $cents, string $currency = 'PLN'): self
    {
        return new self($cents, $currency);
    }

    public static function fromDecimal(float $amount, string $currency = 'PLN'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public static function zero(string $currency = 'PLN'): self
    {
        return new self(0, $currency);
    }

    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function amountAsDecimal(): float
    {
        return $this->amountInCents / 100;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        $result = $this->amountInCents - $other->amountInCents;

        return new self(max(0, $result), $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->amountInCents * $factor), $this->currency);
    }

    public function percentage(int $percent): self
    {
        return new self((int) round($this->amountInCents * $percent / 100), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amountInCents === 0;
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amountInCents > $other->amountInCents;
    }

    public function isGreaterThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amountInCents >= $other->amountInCents;
    }

    public function isLessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amountInCents < $other->amountInCents;
    }

    public function equals(Money $other): bool
    {
        return $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }

    public function format(): string
    {
        return sprintf('%.2f %s', $this->amountAsDecimal(), $this->currency);
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                sprintf('Cannot operate on different currencies: %s and %s', $this->currency, $other->currency)
            );
        }
    }
}

