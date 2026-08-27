<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Media\Controllers\BrowseMediaController;
use Modules\Media\Controllers\CreateMediaController;
use Modules\Media\Controllers\DestroyMediaController;
use Modules\Media\Controllers\EditMediaController;
use Modules\Media\Controllers\ServeMediaFileController;
use Modules\Media\Controllers\ServeMediaThumbnailController;
use Modules\Media\Controllers\ShowMediaController;
use Modules\Media\Controllers\StoreMediaController;
use Modules\Media\Controllers\UpdateMediaController;

Route::middleware('web')->group(function (): void {
    Route::get('posts', BrowseMediaController::class)->name('media.index');
    // Item URLs are /m/{hash_id}: the hash id is already the identifier, so a
    // longer segment carries no information.
    Route::get('m/{media}', ShowMediaController::class)->name('media.show');
    Route::get('m/{media}/file', ServeMediaFileController::class)->name('media.file');
    Route::get('m/{media}/thumbnail/{size}', ServeMediaThumbnailController::class)->name('media.thumbnail');
});

Route::middleware(['web', 'auth'])->group(function (): void {
    // Upload lives outside the m/ prefix so no fixed segment can ever collide
    // with a generated hash id.
    Route::get('upload', CreateMediaController::class)->name('media.create');
    Route::post('upload', StoreMediaController::class)->name('media.store');
    Route::get('m/{media}/edit', EditMediaController::class)->name('media.edit');
    Route::patch('m/{media}', UpdateMediaController::class)->name('media.update');
    Route::delete('m/{media}', DestroyMediaController::class)->name('media.destroy');
});
