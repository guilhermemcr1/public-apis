<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'APIs Publicas',
        'status' => 'ok',
    ]);
});

$docsHub = static fn () => view('docs.hub');

Route::get('/docs', $docsHub)->name('docs.hub');
Route::get('/api/documentation', $docsHub)->name('documentation.hub');
