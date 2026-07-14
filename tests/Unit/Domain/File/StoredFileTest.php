<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\File;

use App\Domain\File\Entity\StoredFile;
use App\Domain\File\ValueObject\FileType;
use App\Domain\File\ValueObject\StoredFileId;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StoredFileTest extends TestCase
{
    public function test_file_types_have_stable_numeric_codes_and_mime_types(): void
    {
        self::assertSame(1, FileType::Pdf->value);
        self::assertSame('application/pdf', FileType::Pdf->mimeType());
        self::assertSame(FileType::Pdf, FileType::fromMimeType('application/pdf'));
        self::assertSame(2, FileType::Docx->value);
        self::assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            FileType::Docx->mimeType(),
        );
    }

    public function test_it_represents_a_retained_supported_file(): void
    {
        $uploadedAt = new DateTimeImmutable('2026-07-14 10:00:00');
        $file = new StoredFile(
            id: new StoredFileId('file-1'),
            originalName: 'contract.pdf',
            storagePath: 'documents/file-1.pdf',
            type: FileType::Pdf,
            sizeBytes: StoredFile::MAX_SIZE_BYTES,
            uploadedAt: $uploadedAt,
            expiresAt: $uploadedAt->modify('+24 hours'),
        );

        self::assertFalse($file->isExpiredAt($uploadedAt->modify('+23 hours')));
        self::assertTrue($file->isExpiredAt($uploadedAt->modify('+24 hours')));
        self::assertSame('pdf', $file->type->extension());
    }

    public function test_it_rejects_a_file_larger_than_ten_megabytes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $uploadedAt = new DateTimeImmutable;

        new StoredFile(
            id: new StoredFileId('file-1'),
            originalName: 'contract.pdf',
            storagePath: 'documents/file-1.pdf',
            type: FileType::Pdf,
            sizeBytes: StoredFile::MAX_SIZE_BYTES + 1,
            uploadedAt: $uploadedAt,
            expiresAt: $uploadedAt->modify('+24 hours'),
        );
    }

    public function test_it_rejects_unsupported_mime_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StoredFile::upload(
            id: new StoredFileId('file-1'),
            originalName: 'payload.exe',
            storagePath: 'files/file-1.exe',
            mimeType: 'application/octet-stream',
            sizeBytes: 100,
            uploadedAt: new DateTimeImmutable,
        );
    }

    public function test_it_rejects_extension_that_does_not_match_mime_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StoredFile::upload(
            id: new StoredFileId('file-1'),
            originalName: 'renamed.docx',
            storagePath: 'files/file-1.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 100,
            uploadedAt: new DateTimeImmutable,
        );
    }
}
