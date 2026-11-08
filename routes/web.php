<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SchoolController;
use Illuminate\Support\Facades\Route;
Route::get('/',[SchoolController::class,'home'])->name('home');
Route::get('/about',fn()=>redirect('/#about')); Route::get('/departments',fn()=>redirect('/#departments'));

// ---- Halaman publik / khusus tamu (belum login) ----
Route::middleware('guest')->group(function(){
 Route::get('/login',[AuthController::class,'loginForm'])->name('login'); Route::post('/login',[AuthController::class,'login']);
 Route::get('/register',[AuthController::class,'registerForm'])->name('register'); Route::post('/register',[AuthController::class,'register']);
});
Route::options('/api/login',[AuthController::class,'apiLogin']); Route::post('/api/login',[AuthController::class,'apiLogin']); Route::post('/api/auth/login',[AuthController::class,'apiLogin']);
Route::get('/api/me',[AuthController::class,'apiMe']);   // menjawab 401 JSON sendiri bila belum login

// ---- Halaman WAJIB LOGIN (dilindungi middleware `auth`) ----
Route::middleware('auth')->group(function(){
 // Route Materi (modul Material) & Fasilitas (modul Facility) dimuat oleh ServiceProvider masing-masing modul (HMVC).
 // /academic dipertahankan sebagai alias agar link lama tetap bekerja.
 Route::get('/academic',fn()=>redirect()->route('materials.index'))->name('academic');
 Route::post('/logout',[AuthController::class,'logout'])->name('logout');
 Route::get('/dashboard',[SchoolController::class,'dashboard'])->name('dashboard');
 Route::get('/quizzes',[SchoolController::class,'quizzes'])->name('quizzes'); Route::post('/quizzes/create',[SchoolController::class,'createQuiz']); Route::get('/quizzes/edit/{id}',[SchoolController::class,'editQuiz']); Route::put('/quizzes/update/{id}',[SchoolController::class,'updateQuiz']); Route::delete('/quizzes/delete/{id}',[SchoolController::class,'deleteQuiz']); Route::get('/quizzes/take/{id}',[SchoolController::class,'takeQuiz']); Route::post('/quizzes/submit/{id}',[SchoolController::class,'submitQuiz']); Route::get('/quizzes/results/{id}',[SchoolController::class,'quizResults']);
 Route::get('/grades',[SchoolController::class,'grades'])->name('grades');
});
