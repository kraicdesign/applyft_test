<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\File\Command\CreateFile\CreateFileCommand;
use App\Application\File\Command\CreateFile\CreateFileHandler;
use App\Application\File\Command\UpdateFile\UpdateFileCommand;
use App\Application\File\Command\UpdateFile\UpdateFileHandler;
use App\Application\File\Query\ListFiles\ListFilesHandler;
use App\Application\File\Query\ListFiles\ListFilesQuery;
use App\Domain\File\ValueObject\FileType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class StoredFileApplicationBindingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_and_list_handlers_use_real_persistence_adapters(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->createWithContent('agreement.pdf', '%PDF-1.7 test document');
        $pdfPath = $pdf->getRealPath();
        self::assertIsString($pdfPath);

        $storedFile = $this->app->make(CreateFileHandler::class)->handle(new CreateFileCommand(
            temporaryPath: $pdfPath,
            originalName: $pdf->getClientOriginalName(),
            mimeType: FileType::Pdf->mimeType(),
            sizeBytes: $pdf->getSize(),
        ));

        self::assertTrue(Str::isUuid($storedFile->id->value));
        self::assertSame(FileType::Pdf, $storedFile->type);
        Storage::disk('local')->assertExists($storedFile->storagePath);
        $this->assertDatabaseHas('stored_files', [
            'id' => $storedFile->id->value,
            'original_name' => 'agreement.pdf',
            'type' => FileType::Pdf->value,
            'size_bytes' => $pdf->getSize(),
        ]);

        $docx = UploadedFile::fake()->createWithContent('agreement.docx', 'PK test document');
        $docxPath = $docx->getRealPath();
        self::assertIsString($docxPath);
        $oldStoragePath = $storedFile->storagePath;

        $updatedFile = $this->app->make(UpdateFileHandler::class)->handle(new UpdateFileCommand(
            fileId: $storedFile->id->value,
            temporaryPath: $docxPath,
            originalName: $docx->getClientOriginalName(),
            mimeType: FileType::Docx->mimeType(),
            sizeBytes: $docx->getSize(),
        ));

        self::assertSame(FileType::Docx, $updatedFile->type);
        Storage::disk('local')->assertMissing($oldStoragePath);
        Storage::disk('local')->assertExists($updatedFile->storagePath);
        $this->assertDatabaseHas('stored_files', [
            'id' => $storedFile->id->value,
            'original_name' => 'agreement.docx',
            'storage_path' => $updatedFile->storagePath,
            'type' => FileType::Docx->value,
            'size_bytes' => $docx->getSize(),
        ]);

        $listedFiles = $this->app->make(ListFilesHandler::class)->handle(new ListFilesQuery);

        self::assertCount(1, $listedFiles);
        self::assertSame($updatedFile->id->value, $listedFiles[0]->id);
    }
}
