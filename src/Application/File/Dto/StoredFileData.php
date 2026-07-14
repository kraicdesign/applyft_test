<?php

declare(strict_types=1);

namespace App\Application\File\Dto;

use App\Domain\File\Entity\StoredFile;
use DateTimeImmutable;

final readonly class StoredFileData
{
    public function __construct(
        public string $id,
        public string $originalName,
        public string $extension,
        public string $mimeType,
        public int $sizeBytes,
        public DateTimeImmutable $uploadedAt,
        public DateTimeImmutable $expiresAt,
    ) {}

    public static function fromEntity(StoredFile $file): self
    {
        return new self(
            id: $file->id->value,
            originalName: $file->originalName,
            extension: $file->type->extension(),
            mimeType: $file->type->mimeType(),
            sizeBytes: $file->sizeBytes,
            uploadedAt: $file->uploadedAt,
            expiresAt: $file->expiresAt,
        );
    }
}
