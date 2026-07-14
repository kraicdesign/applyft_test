<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\File\Command\DeleteFile\DeleteFileCommand;
use App\Application\File\Command\DeleteFile\DeleteFileHandler;
use Illuminate\Http\JsonResponse;

final class DeleteFileController extends Controller
{
    public function __invoke(string $fileId, DeleteFileHandler $handler): JsonResponse
    {
        $handler->handle(new DeleteFileCommand($fileId));

        return response()->json([
            'message' => 'File deleted. Its notification has been queued.',
        ]);
    }
}
