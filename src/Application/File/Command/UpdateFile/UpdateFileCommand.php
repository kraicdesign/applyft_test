<?php

declare(strict_types=1);

namespace App\Application\File\Command\UpdateFile;

final readonly class UpdateFileCommand
{
    public function __construct(
        public string $fileId,
        public string $temporaryPath,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
    ) {}
}
