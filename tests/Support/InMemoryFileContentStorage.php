<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\File\Contract\FileContentStorage;

final class InMemoryFileContentStorage implements FileContentStorage
{
    /** @var array<string, string> */
    public array $stored = [];

    /** @var list<string> */
    public array $deleted = [];

    public function store(string $sourcePath, string $destinationPath): void
    {
        $this->stored[$destinationPath] = $sourcePath;
    }

    public function delete(string $storagePath): void
    {
        unset($this->stored[$storagePath]);
        $this->deleted[] = $storagePath;
    }
}
