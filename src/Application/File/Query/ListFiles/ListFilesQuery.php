<?php

declare(strict_types=1);

namespace App\Application\File\Query\ListFiles;

use InvalidArgumentException;

final readonly class ListFilesQuery
{
    public const int DEFAULT_PER_PAGE = 20;

    public const int MAX_PER_PAGE = 100;

    public function __construct(
        public int $page = 1,
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than zero.');
        }

        if ($perPage < 1 || $perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException(sprintf(
                'Files per page must be between 1 and %d.',
                self::MAX_PER_PAGE,
            ));
        }
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
