<?php

declare(strict_types=1);

namespace App\Domain\File\Contract;

use App\Domain\File\ValueObject\StoredFileId;

interface StoredFileIdGenerator
{
    public function generate(): StoredFileId;
}
