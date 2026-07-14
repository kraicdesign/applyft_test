<?php

declare(strict_types=1);

namespace App\Application\File\Command\DeleteFile;

use App\Domain\File\Contract\FileContentStorage;
use App\Domain\File\Contract\FileDeletionNotificationPublisher;
use App\Domain\File\Contract\StoredFileRepository;
use App\Domain\File\Entity\FileDeletionNotification;
use App\Domain\File\Exception\StoredFileNotFound;
use App\Domain\File\ValueObject\DeletionReason;
use App\Domain\File\ValueObject\StoredFileId;
use App\Domain\Shared\Contract\Clock;
use DateTimeImmutable;

final readonly class DeleteFileHandler
{
    public function __construct(
        private StoredFileRepository $fileRepository,
        private FileContentStorage $contentStorage,
        private FileDeletionNotificationPublisher $notificationPublisher,
        private Clock $clock,
        private string $notificationRecipient,
    ) {}

    public function handle(DeleteFileCommand $command): void
    {
        $this->delete(new StoredFileId($command->fileId), DeletionReason::Manual, $this->clock->now());
    }

    public function delete(StoredFileId $fileId, DeletionReason $reason, DateTimeImmutable $deletedAt): void
    {
        $storedFile = $this->fileRepository->find($fileId)
            ?? throw StoredFileNotFound::withId($fileId);

        $this->contentStorage->delete($storedFile->storagePath);
        $this->fileRepository->remove($fileId);
        $this->notificationPublisher->publish(new FileDeletionNotification(
            recipient: $this->notificationRecipient,
            fileId: $fileId->value,
            originalName: $storedFile->originalName,
            reason: $reason,
            deletedAt: $deletedAt,
        ));
    }
}
