<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\File\Command\CreateFile\CreateFileCommand;
use App\Application\File\Command\CreateFile\CreateFileHandler;
use App\Application\File\Dto\StoredFileData;
use App\Presentation\Http\Request\StoreFileRequest;
use App\Presentation\Http\Response\StoredFileResponseFactory;
use Illuminate\Http\JsonResponse;

final class StoreFileController extends Controller
{
    public function __invoke(StoreFileRequest $request, CreateFileHandler $handler): JsonResponse
    {
        $uploadedFile = $request->uploadedFile();
        $storedFile = $handler->handle(new CreateFileCommand(
            temporaryPath: $uploadedFile->getRealPath(),
            originalName: $uploadedFile->getClientOriginalName(),
            mimeType: $uploadedFile->getMimeType(),
            sizeBytes: $uploadedFile->getSize(),
        ));

        return response()->json([
            'message' => 'Your file is safely stored for 24 hours.',
            'file' => StoredFileResponseFactory::make(StoredFileData::fromEntity($storedFile)),
            'manage_url' => route('files.index'),
        ], 201);
    }
}
