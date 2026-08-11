<?php

use App\Http\Controllers\Panel\DocumentController;
use App\Http\Middleware\RequirePanelUser;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Backend for the SVAR File Manager (Documents page inside the Filament admin panel).
// CSRF-exempt (see bootstrap/app.php) because @svar-ui/filemanager-data-provider's
// RestDataProvider issues raw fetch() calls with no hook for a CSRF header; auth +
// per-request ownership checks in DocumentController carry the security burden instead.
Route::middleware([RequirePanelUser::class])
    ->prefix('panel-api/documents')
    ->name('panel-api.documents.')
    ->group(function () {
        Route::get('/users', [DocumentController::class, 'users'])->name('users');

        Route::prefix('{owner}')->group(function () {
            Route::get('/files', [DocumentController::class, 'index'])->name('index');
            Route::get('/files/{path}', [DocumentController::class, 'children'])->where('path', '.*')->name('children');
            Route::post('/files/{parent}', [DocumentController::class, 'create'])->where('parent', '.*')->name('create');
            Route::put('/files/{id}', [DocumentController::class, 'rename'])->where('id', '.*')->name('rename');
            Route::put('/files', [DocumentController::class, 'bulk'])->name('bulk');
            Route::delete('/files', [DocumentController::class, 'delete'])->name('delete');
            Route::post('/upload', [DocumentController::class, 'upload'])->name('upload');
            Route::get('/info', [DocumentController::class, 'info'])->name('info');
            Route::get('/download', [DocumentController::class, 'download'])->name('download');
        })->whereNumber('owner');
    });
