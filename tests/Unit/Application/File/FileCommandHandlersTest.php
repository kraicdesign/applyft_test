<?php

declare(strict_types=1);

namespace Tests\Unit\Application\File;

use App\Application\File\Command\CreateFile\CreateFileCommand;
use App\Application\File\Command\CreateFile\CreateFileHandler;
use App\Application\File\Command\DeleteExpiredFiles\DeleteExpiredFilesCommand;
use App\Application\File\Command\DeleteExpiredFiles\DeleteExpiredFilesHandler;
use App\Application\File\Command\DeleteFile\DeleteFileCommand;
use App\Application\File\Command\DeleteFile\DeleteFileHandler;
use App\Application\File\Command\UpdateFile\UpdateFileCommand;
use App\Application\File\Command\UpdateFile\UpdateFileHandler;
use App\Domain\File\Entity\StoredFile;
use App\Domain\File\Exception\StoredFileNotFound;
use App\Domain\File\ValueObject\DeletionReason;
use App\Domain\File\ValueObject\StoredFileId;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Support\FixedClock;
use Tests\Support\FixedStoredFileIdGenerator;
use Tests\Support\InMemoryFileContentStorage;
use Tests\Support\InMemoryFileDeletionNotificationPublisher;
use Tests\Support\InMemoryStoredFileRepository;

final class FileCommandHandlersTest extends TestCase
{
    private const string RECIPIENT = 'developer@example.com';

    private DateTimeImmutable $now;

    private InMemoryStoredFileRepository $fileRepository;

    private InMemoryFileContentStorage $contentStorage;

    private InMemoryFileDeletionNotificationPublisher $notificationPublisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = new DateTimeImmutable('2026-07-14 12:00:00');
        $this->fileRepository = new InMemoryStoredFileRepository;
        $this->contentStorage = new InMemoryFileContentStorage;
        $this->notificationPublisher = new InMemoryFileDeletionNotificationPublisher;
    }

    public function test_create_stores_a_domain_valid_file_for_twenty_four_hours(): void
    {
        $file = $this->createFileHandler()->handle(new CreateFileCommand(
            temporaryPath: '/tmp/upload',
            originalName: 'contract.pdf',
            mimeType: 'application/pdf',
            sizeBytes: StoredFile::MAX_SIZE_BYTES,
        ));

        self::assertSame('file-1', $file->id->value);
        self::assertEquals($this->now->modify('+24 hours'), $file->expiresAt);
        self::assertSame('/tmp/upload', $this->contentStorage->stored['files/file-1.pdf']);
        self::assertSame($file, $this->fileRepository->find($file->id));
    }

    public function test_create_rejects_unsupported_format_before_storage_is_touched(): void
    {
        try {
            $this->createFileHandler()->handle(new CreateFileCommand(
                temporaryPath: '/tmp/upload',
                originalName: 'payload.exe',
                mimeType: 'application/octet-stream',
                sizeBytes: 100,
            ));

            self::fail('Unsupported file type was accepted.');
        } catch (InvalidArgumentException) {
            self::assertSame([], $this->contentStorage->stored);
            self::assertSame([], $this->fileRepository->items);
        }
    }

    public function test_create_rejects_file_larger_than_ten_megabytes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createFileHandler()->handle(new CreateFileCommand(
            temporaryPath: '/tmp/upload',
            originalName: 'contract.pdf',
            mimeType: 'application/pdf',
            sizeBytes: StoredFile::MAX_SIZE_BYTES + 1,
        ));
    }

    public function test_update_replaces_content_and_restarts_retention(): void
    {
        $original = $this->createFileHandler()->handle(new CreateFileCommand(
            temporaryPath: '/tmp/old',
            originalName: 'old.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 100,
        ));
        $updatedAt = $this->now->modify('+2 hours');
        $handler = new UpdateFileHandler($this->fileRepository, $this->contentStorage, new FixedClock($updatedAt));

        $updated = $handler->handle(new UpdateFileCommand(
            fileId: $original->id->value,
            temporaryPath: '/tmp/new',
            originalName: 'new.docx',
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            sizeBytes: 200,
        ));

        self::assertSame('new.docx', $updated->originalName);
        self::assertEquals($updatedAt->modify('+24 hours'), $updated->expiresAt);
        self::assertContains('files/file-1.pdf', $this->contentStorage->deleted);
        self::assertSame('/tmp/new', $this->contentStorage->stored['files/file-1.docx']);
    }

    public function test_update_rejects_unknown_file(): void
    {
        $this->expectException(StoredFileNotFound::class);

        (new UpdateFileHandler($this->fileRepository, $this->contentStorage, new FixedClock($this->now)))
            ->handle(new UpdateFileCommand(
                fileId: 'missing',
                temporaryPath: '/tmp/new',
                originalName: 'new.pdf',
                mimeType: 'application/pdf',
                sizeBytes: 200,
            ));
    }

    public function test_manual_delete_removes_file_and_publishes_notification(): void
    {
        $file = $this->createFileHandler()->handle(new CreateFileCommand(
            temporaryPath: '/tmp/upload',
            originalName: 'contract.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 100,
        ));

        $this->deleteHandler()->handle(new DeleteFileCommand($file->id->value));

        self::assertNull($this->fileRepository->find($file->id));
        self::assertContains($file->storagePath, $this->contentStorage->deleted);
        self::assertSame(DeletionReason::Manual, $this->notificationPublisher->published[0]->reason);
    }

    public function test_delete_expired_removes_only_expired_files(): void
    {
        $expired = StoredFile::upload(
            id: new StoredFileId('expired'),
            originalName: 'expired.pdf',
            storagePath: 'files/expired.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 100,
            uploadedAt: $this->now->modify('-25 hours'),
        );
        $active = StoredFile::upload(
            id: new StoredFileId('active'),
            originalName: 'active.pdf',
            storagePath: 'files/active.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 100,
            uploadedAt: $this->now,
        );
        $this->fileRepository->save($expired);
        $this->fileRepository->save($active);
        $handler = new DeleteExpiredFilesHandler($this->fileRepository, $this->deleteHandler());

        $deleted = $handler->handle(new DeleteExpiredFilesCommand($this->now));

        self::assertSame(1, $deleted);
        self::assertNull($this->fileRepository->find($expired->id));
        self::assertSame($active, $this->fileRepository->find($active->id));
        self::assertSame(DeletionReason::RetentionExpired, $this->notificationPublisher->published[0]->reason);
    }

    private function createFileHandler(): CreateFileHandler
    {
        return new CreateFileHandler(
            $this->fileRepository,
            $this->contentStorage,
            new FixedStoredFileIdGenerator('file-1'),
            new FixedClock($this->now),
        );
    }

    private function deleteHandler(): DeleteFileHandler
    {
        return new DeleteFileHandler(
            $this->fileRepository,
            $this->contentStorage,
            $this->notificationPublisher,
            new FixedClock($this->now),
            self::RECIPIENT,
        );
    }
}
