<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Domain\File\Contract\StoredFileIdGenerator;
use App\Domain\File\ValueObject\StoredFileId;
use Illuminate\Support\Str;

final readonly class UuidStoredFileIdGenerator implements StoredFileIdGenerator
{
    public function generate(): StoredFileId
    {
        return new StoredFileId((string) Str::uuid());
    }
}
