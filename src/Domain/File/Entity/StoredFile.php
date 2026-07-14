<?php

declare(strict_types=1);

namespace App\Domain\File\Entity;

use App\Domain\File\ValueObject\FileType;
use App\Domain\File\ValueObject\StoredFileId;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class StoredFile
{
    public const int MAX_SIZE_BYTES = 10 * 1024 * 1024;

    public const int RETENTION_HOURS = 24;

    public function __construct(
        public StoredFileId $id,
        public string $originalName,
        public string $storagePath,
        public FileType $type,
        public int $sizeBytes,
        public DateTimeImmutable $uploadedAt,
        public DateTimeImmutable $expiresAt,
    ) {
        if (trim($originalName) === '') {
            throw new InvalidArgumentException('Original file name cannot be empty.');
        }

        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== $type->extension()) {
            throw new InvalidArgumentException('File extension does not match its supported format.');
        }

        if (trim($storagePath) === '') {
            throw new InvalidArgumentException('Storage path cannot be empty.');
        }

        if ($sizeBytes < 1 || $sizeBytes > self::MAX_SIZE_BYTES) {
            throw new InvalidArgumentException('File size must be between 1 byte and 10 MB.');
        }

        if ($expiresAt <= $uploadedAt) {
            throw new InvalidArgumentException('Expiration must be later than upload time.');
        }
    }

    public function isExpiredAt(DateTimeImmutable $time): bool
    {
        return $this->expiresAt <= $time;
    }

    public static function upload(
        StoredFileId $id,
        string $originalName,
        string $storagePath,
        string $mimeType,
        int $sizeBytes,
        DateTimeImmutable $uploadedAt,
    ): self {
        return new self(
            id: $id,
            originalName: $originalName,
            storagePath: $storagePath,
            type: FileType::fromMimeType($mimeType),
            sizeBytes: $sizeBytes,
            uploadedAt: $uploadedAt,
            expiresAt: $uploadedAt->modify(sprintf('+%d hours', self::RETENTION_HOURS)),
        );
    }

    public function replaceContent(
        string $originalName,
        string $storagePath,
        string $mimeType,
        int $sizeBytes,
        DateTimeImmutable $uploadedAt,
    ): self {
        return self::upload(
            id: $this->id,
            originalName: $originalName,
            storagePath: $storagePath,
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
            uploadedAt: $uploadedAt,
        );
    }
}
