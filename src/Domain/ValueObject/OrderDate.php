<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Value Object representing the order date.
 * Immutable wrapper for DateTimeImmutable with domain methods.
 */
final readonly class OrderDate
{
    private const int FRIDAY = 5;

    private function __construct(
        private DateTimeImmutable $date
    ) {
    }

    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        if ($dateTime instanceof DateTimeImmutable) {
            return new self($dateTime);
        }

        return new self(DateTimeImmutable::createFromInterface($dateTime));
    }

    public static function fromString(string $dateString): self
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $dateString);

        if ($date === false) {
            $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateString);
        }

        if ($date === false) {
            throw new InvalidArgumentException(
                sprintf('Invalid date format: %s. Expected Y-m-d or Y-m-d H:i:s', $dateString)
            );
        }

        return new self($date);
    }

    public static function now(): self
    {
        return new self(new DateTimeImmutable());
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->date;
    }

    public function isFriday(): bool
    {
        return (int) $this->date->format('N') === self::FRIDAY;
    }

    public function dayOfWeek(): int
    {
        return (int) $this->date->format('N');
    }

    public function dayOfWeekName(): string
    {
        return $this->date->format('l');
    }

    public function format(string $format = 'Y-m-d'): string
    {
        return $this->date->format($format);
    }

    public function equals(OrderDate $other): bool
    {
        return $this->date->format('Y-m-d') === $other->date->format('Y-m-d');
    }

    public function __toString(): string
    {
        return $this->format();
    }
}

