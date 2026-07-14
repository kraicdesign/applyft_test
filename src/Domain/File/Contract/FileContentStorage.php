<?php

declare(strict_types=1);

namespace App\Domain\File\Contract;

interface FileContentStorage
{
    public function store(string $sourcePath, string $destinationPath): void;

    public function delete(string $storagePath): void;
}
