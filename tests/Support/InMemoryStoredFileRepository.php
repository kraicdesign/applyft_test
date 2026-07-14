<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\File\Contract\StoredFileRepository;
use App\Domain\File\Entity\StoredFile;
use App\Domain\File\ValueObject\StoredFileId;
use DateTimeImmutable;

final class InMemoryStoredFileRepository implements StoredFileRepository
{
    /** @var array<string, StoredFile> */
    public array $items = [];

    public function save(StoredFile $file): void
    {
        $this->items[$file->id->value] = $file;
    }

    public function find(StoredFileId $id): ?StoredFile
    {
        return $this->items[$id->value] ?? null;
    }

    public function all(): iterable
    {
        return array_values($this->items);
    }

    public function expiredAt(DateTimeImmutable $time): iterable
    {
        return array_values(array_filter(
            $this->items,
            static fn (StoredFile $file): bool => $file->isExpiredAt($time),
        ));
    }

    public function remove(StoredFileId $id): void
    {
        unset($this->items[$id->value]);
    }
}
