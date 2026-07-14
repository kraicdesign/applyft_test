<?php

declare(strict_types=1);

namespace App\Application\File\Command\DeleteExpiredFiles;

use App\Application\File\Command\DeleteFile\DeleteFileHandler;
use App\Domain\File\Contract\StoredFileRepository;
use App\Domain\File\ValueObject\DeletionReason;

final readonly class DeleteExpiredFilesHandler
{
    public function __construct(
        private StoredFileRepository $fileRepository,
        private DeleteFileHandler $deleteFileHandler,
    ) {}

    public function handle(DeleteExpiredFilesCommand $command): int
    {
        $deletedFileCount = 0;

        foreach ($this->fileRepository->expiredAt($command->asOf) as $storedFile) {
            $this->deleteFileHandler->delete(
                $storedFile->id,
                DeletionReason::RetentionExpired,
                $command->asOf,
            );
            $deletedFileCount++;
        }

        return $deletedFileCount;
    }
}
