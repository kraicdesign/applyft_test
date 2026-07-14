<?php

declare(strict_types=1);

namespace App\Application\File\Command\UpdateFile;

use App\Domain\File\Contract\FileContentStorage;
use App\Domain\File\Contract\StoredFileRepository;
use App\Domain\File\Entity\StoredFile;
use App\Domain\File\Exception\StoredFileNotFound;
use App\Domain\File\ValueObject\FileType;
use App\Domain\File\ValueObject\StoredFileId;
use App\Domain\Shared\Contract\Clock;

final readonly class UpdateFileHandler
{
    public function __construct(
        private StoredFileRepository $fileRepository,
        private FileContentStorage $contentStorage,
        private Clock $clock,
    ) {}

    public function handle(UpdateFileCommand $command): StoredFile
    {
        $fileId = new StoredFileId($command->fileId);
        $existingFile = $this->fileRepository->find($fileId)
            ?? throw StoredFileNotFound::withId($fileId);
        $fileType = FileType::fromMimeType($command->mimeType);
        $storagePath = sprintf('files/%s.%s', $existingFile->id->value, $fileType->extension());
        $updatedFile = $existingFile->replaceContent(
            originalName: $command->originalName,
            storagePath: $storagePath,
            mimeType: $command->mimeType,
            sizeBytes: $command->sizeBytes,
            uploadedAt: $this->clock->now(),
        );

        $this->contentStorage->store($command->temporaryPath, $storagePath);
        $this->fileRepository->save($updatedFile);

        if ($existingFile->storagePath !== $storagePath) {
            $this->contentStorage->delete($existingFile->storagePath);
        }

        return $updatedFile;
    }
}
