<?php

declare(strict_types=1);

namespace App\Domain\File\Exception;

use App\Domain\File\ValueObject\StoredFileId;
use RuntimeException;

final class StoredFileNotFound extends RuntimeException
{
    public static function withId(StoredFileId $id): self
    {
        return new self(sprintf('Stored file "%s" was not found.', $id->value));
    }
}
