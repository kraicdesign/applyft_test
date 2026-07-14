<?php

declare(strict_types=1);

namespace Tests\Unit\Application\File;

use App\Application\File\Query\ListFiles\ListFilesHandler;
use App\Application\File\Query\ListFiles\ListFilesQuery;
use App\Domain\File\Entity\StoredFile;
use App\Domain\File\ValueObject\StoredFileId;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryStoredFileRepository;

final class ListFilesHandlerTest extends TestCase
{
    public function test_it_returns_the_requested_page_newest_first(): void
    {
        $fileRepository = new InMemoryStoredFileRepository;
        $fileRepository->save($this->storedFile('oldest', '2026-07-14 10:00:00'));
        $fileRepository->save($this->storedFile('newest', '2026-07-14 12:00:00'));
        $fileRepository->save($this->storedFile('middle', '2026-07-14 11:00:00'));

        $storedFiles = (new ListFilesHandler($fileRepository))->handle(
            new ListFilesQuery(page: 2, perPage: 2),
        );

        self::assertCount(1, $storedFiles);
        self::assertSame('oldest', $storedFiles[0]->id);
    }

    public function test_it_rejects_an_invalid_page(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListFilesQuery(page: 0);
    }

    public function test_it_rejects_a_page_size_above_the_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListFilesQuery(perPage: ListFilesQuery::MAX_PER_PAGE + 1);
    }

    private function storedFile(string $id, string $uploadedAt): StoredFile
    {
        return StoredFile::upload(
            id: new StoredFileId($id),
            originalName: sprintf('%s.pdf', $id),
            storagePath: sprintf('files/%s.pdf', $id),
            mimeType: 'application/pdf',
            sizeBytes: 100,
            uploadedAt: new DateTimeImmutable($uploadedAt),
        );
    }
}
