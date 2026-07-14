<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;

final class FileUploadPageController extends Controller
{
    public function __invoke(): View
    {
        return view('files.upload');
    }
}
