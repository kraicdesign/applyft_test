<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\File\Contract\StoredFileIdGenerator;
use App\Domain\File\ValueObject\StoredFileId;

final readonly class FixedStoredFileIdGenerator implements StoredFileIdGenerator
{
    public function __construct(private string $id) {}

    public function generate(): StoredFileId
    {
        return new StoredFileId($this->id);
    }
}
