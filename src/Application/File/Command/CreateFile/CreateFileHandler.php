<?php

declare(strict_types=1);

namespace App\Application\File\Command\CreateFile;

use App\Domain\File\Contract\FileContentStorage;
use App\Domain\File\Contract\StoredFileIdGenerator;
use App\Domain\File\Contract\StoredFileRepository;
use App\Domain\File\Entity\StoredFile;
use App\Domain\File\ValueObject\FileType;
use App\Domain\Shared\Contract\Clock;
use Throwable;

final readonly class CreateFileHandler
{
    public function __construct(
        private StoredFileRepository $fileRepository,
        private FileContentStorage $contentStorage,
        private StoredFileIdGenerator $fileIdGenerator,
        private Clock $clock,
    ) {}

    public function handle(CreateFileCommand $command): StoredFile
    {
        $fileId = $this->fileIdGenerator->generate();
        $fileType = FileType::fromMimeType($command->mimeType);
        $storagePath = sprintf('files/%s.%s', $fileId->value, $fileType->extension());
        $storedFile = StoredFile::upload(
            id: $fileId,
            originalName: $command->originalName,
            storagePath: $storagePath,
            mimeType: $command->mimeType,
            sizeBytes: $command->sizeBytes,
            uploadedAt: $this->clock->now(),
        );

        $this->contentStorage->store($command->temporaryPath, $storagePath);

        try {
            $this->fileRepository->save($storedFile);
        } catch (Throwable $exception) {
            $this->contentStorage->delete($storagePath);

            throw $exception;
        }

        return $storedFile;
    }
}
