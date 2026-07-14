<?php

declare(strict_types=1);

namespace App\Domain\File\ValueObject;

use InvalidArgumentException;

final readonly class StoredFileId
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Stored file ID cannot be empty.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
