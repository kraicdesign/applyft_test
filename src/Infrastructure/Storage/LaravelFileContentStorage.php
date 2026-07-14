<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\File\Contract\FileContentStorage;
use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;

final readonly class LaravelFileContentStorage implements FileContentStorage
{
    public function __construct(private Filesystem $filesystem) {}

    public function store(string $sourcePath, string $destinationPath): void
    {
        $source = fopen($sourcePath, 'rb');

        if ($source === false) {
            throw new RuntimeException(sprintf('Unable to open temporary file "%s".', $sourcePath));
        }

        try {
            if (! $this->filesystem->put($destinationPath, $source)) {
                throw new RuntimeException(sprintf('Unable to store file at "%s".', $destinationPath));
            }
        } finally {
            fclose($source);
        }
    }

    public function delete(string $storagePath): void
    {
        if (! $this->filesystem->exists($storagePath)) {
            return;
        }

        if (! $this->filesystem->delete($storagePath)) {
            throw new RuntimeException(sprintf('Unable to delete file at "%s".', $storagePath));
        }
    }
}
