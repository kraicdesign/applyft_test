<?php

declare(strict_types=1);

namespace App\Application\File\Command\CreateFile;

final readonly class CreateFileCommand
{
    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
    ) {}
}
