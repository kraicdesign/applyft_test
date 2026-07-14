<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\File\Contract\FileDeletionNotificationPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InMemoryFileDeletionNotificationPublisher;
use Tests\TestCase;

final class FileHttpWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajax_upload_list_replace_and_delete_workflow(): void
    {
        Storage::fake('local');
        $notificationPublisher = new InMemoryFileDeletionNotificationPublisher;
        $this->app->instance(FileDeletionNotificationPublisher::class, $notificationPublisher);

        $uploadResponse = $this->withHeader('Accept', 'application/json')->post(route('files.store'), [
            'file' => $this->pdf('agreement.pdf', 'initial content'),
        ]);

        $uploadResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Your file is safely stored for 24 hours.')
            ->assertJsonPath('file.original_name', 'agreement.pdf')
            ->assertJsonPath('file.extension', 'pdf');
        $fileId = $uploadResponse->json('file.id');
        self::assertIsString($fileId);
        $storedPath = sprintf('files/%s.pdf', $fileId);
        Storage::disk('local')->assertExists($storedPath);
        $this->assertDatabaseHas('stored_files', [
            'id' => $fileId,
            'original_name' => 'agreement.pdf',
        ]);

        $this->get(route('files.index'))
            ->assertOk()
            ->assertSee('agreement.pdf')
            ->assertSee('PDF document')
            ->assertSee('file-actions-menu')
            ->assertSee('data-bs-boundary="viewport"', false);

        $replaceResponse = $this->withHeader('Accept', 'application/json')->post(
            route('files.update', $fileId),
            ['file' => $this->pdf('revised-agreement.pdf', 'revised content')],
        );

        $replaceResponse
            ->assertOk()
            ->assertJsonPath('file.id', $fileId)
            ->assertJsonPath('file.original_name', 'revised-agreement.pdf');
        Storage::disk('local')->assertExists($storedPath);
        $this->assertDatabaseHas('stored_files', [
            'id' => $fileId,
            'original_name' => 'revised-agreement.pdf',
        ]);

        $this->withHeader('Accept', 'application/json')
            ->delete(route('files.destroy', $fileId))
            ->assertOk()
            ->assertJsonPath('message', 'File deleted. Its notification has been queued.');

        Storage::disk('local')->assertMissing($storedPath);
        $this->assertDatabaseMissing('stored_files', ['id' => $fileId]);
        self::assertCount(1, $notificationPublisher->published);
        self::assertSame('revised-agreement.pdf', $notificationPublisher->published[0]->originalName);
    }

    public function test_upload_repeats_size_and_format_validation_in_presentation(): void
    {
        Storage::fake('local');

        $response = $this->withHeader('Accept', 'application/json')->post(route('files.store'), [
            'file' => UploadedFile::fake()->createWithContent('payload.exe', "%PDF-1.4\ninvalid extension"),
        ]);
        $response->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->withHeader('Accept', 'application/json')->post(route('files.store'), [
            'file' => UploadedFile::fake()->create('too-large.pdf', 10 * 1024 + 1, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('stored_files', 0);
    }

    private function pdf(string $name, string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj\n{$content}\nendobj\n%%EOF",
        );
    }
}
