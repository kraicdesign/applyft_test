<?php

declare(strict_types=1);

namespace App\Domain\File\Contract;

use App\Domain\File\Entity\StoredFile;
use App\Domain\File\ValueObject\StoredFileId;
use DateTimeImmutable;

interface StoredFileRepository
{
    public function save(StoredFile $file): void;

    public function find(StoredFileId $id): ?StoredFile;

    /** @return iterable<StoredFile> */
    public function all(): iterable;

    /** @return iterable<StoredFile> */
    public function expiredAt(DateTimeImmutable $time): iterable;

    public function remove(StoredFileId $id): void;
}
