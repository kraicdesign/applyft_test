<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repository;

use App\Domain\File\Contract\StoredFileRepository;
use App\Domain\File\Entity\StoredFile;
use App\Domain\File\ValueObject\FileType;
use App\Domain\File\ValueObject\StoredFileId;
use App\Infrastructure\Persistence\Eloquent\Model\StoredFileRecord;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentStoredFileRepository implements StoredFileRepository
{
    public function save(StoredFile $file): void
    {
        $record = StoredFileRecord::query()->find($file->id->value) ?? new StoredFileRecord;
        $record->id = $file->id->value;
        $record->original_name = $file->originalName;
        $record->storage_path = $file->storagePath;
        $record->type = $file->type->value;
        $record->size_bytes = $file->sizeBytes;
        $record->uploaded_at = $file->uploadedAt;
        $record->expires_at = $file->expiresAt;
        $record->save();
    }

    public function find(StoredFileId $id): ?StoredFile
    {
        $record = StoredFileRecord::query()->find($id->value);

        return $record instanceof StoredFileRecord ? $this->toEntity($record) : null;
    }

    public function all(): iterable
    {
        foreach ($this->orderedQuery()->cursor() as $record) {
            yield $this->toEntity($record);
        }
    }

    public function expiredAt(DateTimeImmutable $time): iterable
    {
        $records = StoredFileRecord::query()
            ->where('expires_at', '<=', $time)
            ->orderBy('expires_at')
            ->orderBy('id')
            ->cursor();

        foreach ($records as $record) {
            yield $this->toEntity($record);
        }
    }

    public function remove(StoredFileId $id): void
    {
        StoredFileRecord::query()->whereKey($id->value)->delete();
    }

    /** @return Builder<StoredFileRecord> */
    private function orderedQuery(): Builder
    {
        return StoredFileRecord::query()
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id');
    }

    private function toEntity(StoredFileRecord $record): StoredFile
    {
        return new StoredFile(
            id: new StoredFileId((string) $record->id),
            originalName: (string) $record->original_name,
            storagePath: (string) $record->storage_path,
            type: FileType::from((int) $record->type),
            sizeBytes: (int) $record->size_bytes,
            uploadedAt: new DateTimeImmutable($record->uploaded_at->format('Y-m-d\TH:i:s.uP')),
            expiresAt: new DateTimeImmutable($record->expires_at->format('Y-m-d\TH:i:s.uP')),
        );
    }
}
