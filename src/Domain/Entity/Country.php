<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Country entity (from database).
 */
final readonly class Country
{
    private function __construct(
        private int $id,
        private string $code,
        private string $name,
        private bool $active,
    ) {
        $this->validate();
    }

    public static function create(int $id, string $code, string $name, bool $active = true): self
    {
        return new self($id, strtoupper(trim($code)), trim($name), $active);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function equals(Country $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    private function validate(): void
    {
        if (2 !== strlen($this->code)) {
            throw new \InvalidArgumentException(sprintf('Country code must be exactly 2 characters, got: %s', $this->code));
        }

        if (!preg_match('/^[A-Z]{2}$/', $this->code)) {
            throw new \InvalidArgumentException(sprintf('Country code must contain only uppercase letters, got: %s', $this->code));
        }

        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('Country name cannot be empty');
        }
    }
}
