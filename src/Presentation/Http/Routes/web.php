<?php

declare(strict_types=1);

namespace App\Presentation\Http\Routes;

use App\Presentation\Http\Controllers\DeleteFileController;
use App\Presentation\Http\Controllers\FileIndexController;
use App\Presentation\Http\Controllers\FileUploadPageController;
use App\Presentation\Http\Controllers\StoreFileController;
use App\Presentation\Http\Controllers\UpdateFileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/upload')->name('home');
Route::get('/upload', FileUploadPageController::class)->name('files.upload');
Route::post('/files', StoreFileController::class)->name('files.store');
Route::get('/files', FileIndexController::class)->name('files.index');
Route::post('/files/{fileId}/replace', UpdateFileController::class)->name('files.update');
Route::delete('/files/{fileId}', DeleteFileController::class)->name('files.destroy');
