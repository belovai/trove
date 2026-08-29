<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Middleware\EnsureEmailIsVerified;
use Modules\Tag\Controllers\AutocompleteTagsController;
use Modules\Tag\Controllers\DestroyAliasController;
use Modules\Tag\Controllers\DestroyImplicationController;
use Modules\Tag\Controllers\DestroyTagCategoryController;
use Modules\Tag\Controllers\DestroyTagController;
use Modules\Tag\Controllers\ExportTaxonomyController;
use Modules\Tag\Controllers\ImportTaxonomyController;
use Modules\Tag\Controllers\IndexTagsController;
use Modules\Tag\Controllers\MergeTagController;
use Modules\Tag\Controllers\ShowTagController;
use Modules\Tag\Controllers\ShowTagSettingsController;
use Modules\Tag\Controllers\StoreAliasController;
use Modules\Tag\Controllers\StoreImplicationController;
use Modules\Tag\Controllers\StoreTagCategoryController;
use Modules\Tag\Controllers\UpdateTagCategoryController;
use Modules\Tag\Controllers\UpdateTagController;

Route::middleware('web')->group(function (): void {
    Route::get('tags', IndexTagsController::class)->name('tags.index');
    // Declared before tags/{tag}: a fixed segment must win over the wildcard.
    // "autocomplete" is also a reserved tag name, so the two can never collide.
    Route::get('tags/autocomplete', AutocompleteTagsController::class)->name('tags.autocomplete');
    Route::get('tags/{tag}', ShowTagController::class)->name('tags.show');
});

Route::middleware(['web', 'auth', EnsureEmailIsVerified::class])->group(function (): void {
    Route::patch('tags/{tag}', UpdateTagController::class)->name('tags.update');
    Route::delete('tags/{tag}', DestroyTagController::class)->name('tags.destroy');
    Route::post('tags/{tag}/aliases', StoreAliasController::class)->name('tags.aliases.store');
    Route::delete('tags/{tag}/aliases/{alias}', DestroyAliasController::class)->name('tags.aliases.destroy');
    Route::post('tags/{tag}/implications', StoreImplicationController::class)->name('tags.implications.store');
    Route::delete('tags/{tag}/implications/{implied}', DestroyImplicationController::class)
        ->name('tags.implications.destroy');
    Route::post('tags/{tag}/merge', MergeTagController::class)->name('tags.merge');
});

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('settings/tags', ShowTagSettingsController::class)->name('settings.tags');
    Route::redirect('admin/tags', '/settings/tags');
});

Route::middleware(['web', 'auth', EnsureEmailIsVerified::class])->prefix('admin/tags')->group(function (): void {
    Route::post('categories', StoreTagCategoryController::class)->name('admin.tags.categories.store');
    Route::patch('categories/{category}', UpdateTagCategoryController::class)
        ->name('admin.tags.categories.update');
    Route::delete('categories/{category}', DestroyTagCategoryController::class)
        ->name('admin.tags.categories.destroy');
    Route::get('taxonomy', ExportTaxonomyController::class)->name('admin.tags.taxonomy.export');
    Route::post('taxonomy', ImportTaxonomyController::class)->name('admin.tags.taxonomy.import');
});
