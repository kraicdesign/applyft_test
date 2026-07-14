<?php

declare(strict_types=1);

namespace App\Domain\File\ValueObject;

use InvalidArgumentException;

enum FileType: int
{
    case Pdf = 1;
    case Docx = 2;

    public static function fromMimeType(string $mimeType): self
    {
        return match (strtolower(trim($mimeType))) {
            'application/pdf' => self::Pdf,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => self::Docx,
            default => throw new InvalidArgumentException('Only PDF and DOCX files are supported.'),
        };
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Pdf => 'application/pdf',
            self::Docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::Pdf => 'pdf',
            self::Docx => 'docx',
        };
    }
}
