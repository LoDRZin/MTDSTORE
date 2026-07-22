<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/download-export', function (\Illuminate\Http\Request $request) {
    if (!auth()->check()) {
        abort(403);
    }
    
    $file = base64_decode($request->query('file'));
    if (!$file || !str_starts_with($file, 'exports/')) {
        abort(404);
    }

    $path = storage_path('app/' . $file);
    if (!file_exists($path)) {
        abort(404);
    }

    return response()->download($path)->deleteFileAfterSend(true);
})->name('admin.download-export')->middleware('web');
