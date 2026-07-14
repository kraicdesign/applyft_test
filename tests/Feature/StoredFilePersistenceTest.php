<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\File\Contract\StoredFileRepository;
use App\Domain\File\Entity\StoredFile;
use App\Domain\File\ValueObject\FileType;
use App\Domain\File\ValueObject\StoredFileId;
use App\Infrastructure\Persistence\Eloquent\Model\StoredFileRecord;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class StoredFilePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_and_reconstitutes_all_file_information(): void
    {
        self::assertSame('Y-m-d H:i:s.u', (new StoredFileRecord)->getDateFormat());
        $repository = $this->app->make(StoredFileRepository::class);
        $file = new StoredFile(
            id: new StoredFileId('018fa388-65a4-7ad2-b84d-7a34843e4d53'),
            originalName: 'agreement.pdf',
            storagePath: 'files/018fa388-65a4-7ad2-b84d-7a34843e4d53.pdf',
            type: FileType::Pdf,
            sizeBytes: 1024,
            uploadedAt: new DateTimeImmutable('2026-07-14T12:00:00.123456+00:00'),
            expiresAt: new DateTimeImmutable('2026-07-15T12:00:00.123456+00:00'),
        );

        $repository->save($file);

        $this->assertDatabaseHas('stored_files', [
            'id' => $file->id->value,
            'original_name' => 'agreement.pdf',
            'storage_path' => $file->storagePath,
            'type' => FileType::Pdf->value,
            'size_bytes' => 1024,
        ]);
        self::assertSame(
            '2026-07-14 12:00:00.123456',
            DB::table('stored_files')->where('id', $file->id->value)->value('uploaded_at'),
        );

        $restoredFile = $repository->find($file->id);

        self::assertNotNull($restoredFile);
        self::assertSame($file->id->value, $restoredFile->id->value);
        self::assertSame($file->originalName, $restoredFile->originalName);
        self::assertSame($file->storagePath, $restoredFile->storagePath);
        self::assertSame($file->type, $restoredFile->type);
        self::assertSame($file->sizeBytes, $restoredFile->sizeBytes);
        self::assertSame($file->uploadedAt->format('Y-m-d H:i:s.u'), $restoredFile->uploadedAt->format('Y-m-d H:i:s.u'));
        self::assertSame($file->expiresAt->format('Y-m-d H:i:s.u'), $restoredFile->expiresAt->format('Y-m-d H:i:s.u'));
    }

    public function test_it_queries_expired_files_and_removes_records(): void
    {
        $repository = $this->app->make(StoredFileRepository::class);
        $expiredFile = $this->file('018fa388-65a4-7ad2-b84d-7a34843e4d53', 'old.pdf', '2026-07-13T12:00:00+00:00');
        $retainedFile = $this->file('018fa388-65a4-7ad2-b84d-7a34843e4d54', 'new.pdf', '2026-07-15T12:00:01+00:00');
        $repository->save($expiredFile);
        $repository->save($retainedFile);

        $expiredFiles = iterator_to_array($repository->expiredAt(
            new DateTimeImmutable('2026-07-15T12:00:00+00:00'),
        ));

        self::assertCount(1, $expiredFiles);
        self::assertSame($expiredFile->id->value, $expiredFiles[0]->id->value);

        $repository->remove($expiredFile->id);

        self::assertNull($repository->find($expiredFile->id));
        self::assertNotNull($repository->find($retainedFile->id));
    }

    public function test_migration_defines_column_constraints_and_query_indexes(): void
    {
        $columns = collect(Schema::getColumns('stored_files'))->keyBy('name');
        $indexNames = array_column(Schema::getIndexes('stored_files'), 'name');

        self::assertContains($columns->get('type')['type_name'], ['tinyint', 'integer']);
        self::assertFalse($columns->get('type')['nullable']);
        self::assertFalse($columns->get('uploaded_at')['nullable']);
        self::assertContains('stored_files_expires_at_index', $indexNames);
        self::assertContains('stored_files_uploaded_at_id_index', $indexNames);
        self::assertContains('stored_files_storage_path_unique', $indexNames);
    }

    private function file(string $id, string $name, string $expiresAt): StoredFile
    {
        return new StoredFile(
            id: new StoredFileId($id),
            originalName: $name,
            storagePath: sprintf('files/%s.pdf', $id),
            type: FileType::Pdf,
            sizeBytes: 1024,
            uploadedAt: new DateTimeImmutable($expiresAt)->modify('-24 hours'),
            expiresAt: new DateTimeImmutable($expiresAt),
        );
    }
}
