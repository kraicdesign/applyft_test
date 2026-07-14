<?php

declare(strict_types=1);

namespace App\Application\File\Query\ListFiles;

use App\Application\File\Dto\StoredFileData;
use App\Domain\File\Contract\StoredFileRepository;
use App\Domain\File\Entity\StoredFile;

final readonly class ListFilesHandler
{
    public function __construct(private StoredFileRepository $fileRepository) {}

    /** @return list<StoredFileData> */
    public function handle(ListFilesQuery $query): array
    {
        $storedFiles = [];

        foreach ($this->fileRepository->all() as $storedFile) {
            $storedFiles[] = $storedFile;
        }

        usort(
            $storedFiles,
            static fn (StoredFile $left, StoredFile $right): int => [
                $right->uploadedAt->getTimestamp(),
                $right->id->value,
            ] <=> [
                $left->uploadedAt->getTimestamp(),
                $left->id->value,
            ],
        );

        return array_map(
            StoredFileData::fromEntity(...),
            array_slice($storedFiles, $query->offset(), $query->perPage),
        );
    }
}
