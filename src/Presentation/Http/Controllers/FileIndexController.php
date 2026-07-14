<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\File\Query\ListFiles\ListFilesHandler;
use App\Application\File\Query\ListFiles\ListFilesQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class FileIndexController extends Controller
{
    public function __invoke(Request $request, ListFilesHandler $handler): View
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = ListFilesQuery::DEFAULT_PER_PAGE;
        $files = $handler->handle(new ListFilesQuery($page, $perPage));

        return view('files.index', [
            'files' => $files,
            'page' => $page,
            'hasNextPage' => count($files) === $perPage,
        ]);
    }
}
