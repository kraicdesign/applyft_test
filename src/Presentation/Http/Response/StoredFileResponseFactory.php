<?php

declare(strict_types=1);

namespace App\Presentation\Http\Response;

use App\Application\File\Dto\StoredFileData;

final readonly class StoredFileResponseFactory
{
    /** @return array<string, int|string> */
    public static function make(StoredFileData $file): array
    {
        return [
            'id' => $file->id,
            'original_name' => $file->originalName,
            'extension' => $file->extension,
            'mime_type' => $file->mimeType,
            'size_bytes' => $file->sizeBytes,
            'uploaded_at' => $file->uploadedAt->format(DATE_ATOM),
            'expires_at' => $file->expiresAt->format(DATE_ATOM),
        ];
    }
}
