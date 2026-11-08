<?php

use Illuminate\Support\Facades\Route;
use Modules\Facility\Http\Controllers\FacilityController;

/*
 | Modul Facility (CRUD AJAX + upload).
 | - auth              : wajib login
 | - role:guru,siswa   : halaman, data JSON, hapus, dan buka lampiran (orang tua -> 403)
 | - role:siswa        : hanya siswa yang mengajukan / mengubah pengajuannya
 | - role:guru         : hanya guru yang memproses (ubah status) pengajuan
 */
Route::middleware(['auth', 'role:siswa'])->group(function () {
    Route::post('/facilities', [FacilityController::class, 'store'])->name('facilities.store');
    Route::put('/facilities/{facilityRequest}', [FacilityController::class, 'update'])->name('facilities.update')->whereNumber('facilityRequest');
});

Route::middleware(['auth', 'role:guru'])->group(function () {
    Route::patch('/facilities/{facilityRequest}/status', [FacilityController::class, 'status'])->name('facilities.status')->whereNumber('facilityRequest');
});

Route::middleware(['auth', 'role:guru,siswa'])->group(function () {
    Route::get('/facilities', [FacilityController::class, 'index'])->name('facilities.index');
    Route::get('/facilities/list', [FacilityController::class, 'list'])->name('facilities.list');
    Route::get('/facilities/{facilityRequest}/attachment', [FacilityController::class, 'attachment'])->name('facilities.attachment')->whereNumber('facilityRequest');
    Route::delete('/facilities/{facilityRequest}', [FacilityController::class, 'destroy'])->name('facilities.destroy')->whereNumber('facilityRequest');
});
