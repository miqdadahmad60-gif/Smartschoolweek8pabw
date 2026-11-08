<?php

use Illuminate\Support\Facades\Route;
use Modules\Material\Http\Controllers\MaterialController;

/*
 | Modul Material (CRUD synchronous).
 | - auth          : seluruh halaman wajib login
 | - role:guru     : hanya guru yang boleh menambah / mengubah / menghapus materi
 | - role:guru,siswa : guru & siswa boleh melihat daftar, detail, dan mengunduh (orang tua -> 403)
 | Catatan: grup guru didaftarkan lebih dulu agar /materials/create tidak tertangkap /materials/{material}.
 */
Route::middleware(['auth', 'role:guru'])->group(function () {
    Route::get('/materials/create', [MaterialController::class, 'create'])->name('materials.create');
    Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{material}/edit', [MaterialController::class, 'edit'])->name('materials.edit')->whereNumber('material');
    Route::put('/materials/{material}', [MaterialController::class, 'update'])->name('materials.update')->whereNumber('material');
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy')->whereNumber('material');
});

Route::middleware(['auth', 'role:guru,siswa'])->group(function () {
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/materials/{material}', [MaterialController::class, 'show'])->name('materials.show')->whereNumber('material');
    Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download')->whereNumber('material');
});
