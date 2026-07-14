<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\File\Command\UpdateFile\UpdateFileCommand;
use App\Application\File\Command\UpdateFile\UpdateFileHandler;
use App\Application\File\Dto\StoredFileData;
use App\Presentation\Http\Request\UpdateFileRequest;
use App\Presentation\Http\Response\StoredFileResponseFactory;
use Illuminate\Http\JsonResponse;

final class UpdateFileController extends Controller
{
    public function __invoke(string $fileId, UpdateFileRequest $request, UpdateFileHandler $handler): JsonResponse
    {
        $uploadedFile = $request->uploadedFile();
        $storedFile = $handler->handle(new UpdateFileCommand(
            fileId: $fileId,
            temporaryPath: $uploadedFile->getRealPath(),
            originalName: $uploadedFile->getClientOriginalName(),
            mimeType: $uploadedFile->getMimeType(),
            sizeBytes: $uploadedFile->getSize(),
        ));

        return response()->json([
            'message' => 'File replaced. Its 24-hour retention period has restarted.',
            'file' => StoredFileResponseFactory::make(StoredFileData::fromEntity($storedFile)),
        ]);
    }
}
