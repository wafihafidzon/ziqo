<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Pengajar;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/jelajah', [QuizController::class, 'index'])->name('jelajah');
Route::get('/kuis/{id}', [QuizController::class, 'show'])->whereNumber('id')->name('kuis.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/kuis/{id}', [QuizController::class, 'start'])->whereNumber('id');

    Route::get('/kerjakan/{id}', [PlayController::class, 'show'])->whereNumber('id')->name('kerjakan');
    Route::post('/kerjakan/{id}', [PlayController::class, 'handle'])->whereNumber('id');
    Route::get('/hasil/{id}', [PlayController::class, 'result'])->whereNumber('id')->name('hasil');

    Route::get('/room', [RoomController::class, 'show'])->name('room');
    Route::post('/room', [RoomController::class, 'join'])->middleware('throttle:20,1');

    Route::get('/riwayat', [StudentController::class, 'history'])->name('riwayat');
    Route::post('/riwayat', [StudentController::class, 'resetHistory']);
    Route::get('/pencapaian', [StudentController::class, 'achievements'])->name('pencapaian');

    Route::get('/profil', [ProfileController::class, 'show'])->name('profil');
    Route::post('/profil', [ProfileController::class, 'update']);

    Route::middleware('role:pengajar,admin')->prefix('pengajar')->name('pengajar.')->group(function () {
        Route::get('/kuis', [Pengajar\QuizController::class, 'index'])->name('kuis');
        Route::post('/kuis', [Pengajar\QuizController::class, 'action']);
        Route::match(['get', 'post'], '/kuis/buat', [Pengajar\QuizController::class, 'form'])->name('kuis.create');
        Route::match(['get', 'post'], '/kuis/{id}/edit', [Pengajar\QuizController::class, 'form'])->whereNumber('id')->name('kuis.edit');

        Route::get('/kuis/{kuis}/soal', [Pengajar\QuestionController::class, 'index'])->whereNumber('kuis')->name('soal');
        Route::post('/kuis/{kuis}/soal', [Pengajar\QuestionController::class, 'action'])->whereNumber('kuis');
        Route::match(['get', 'post'], '/kuis/{kuis}/soal/buat', [Pengajar\QuestionController::class, 'form'])->whereNumber('kuis')->name('soal.create');
        Route::match(['get', 'post'], '/kuis/{kuis}/soal/{id}/edit', [Pengajar\QuestionController::class, 'form'])->whereNumber(['kuis', 'id'])->name('soal.edit');

        Route::get('/kuis/{kuis}/room', [Pengajar\RoomController::class, 'index'])->whereNumber('kuis')->name('room');
        Route::post('/kuis/{kuis}/room', [Pengajar\RoomController::class, 'action'])->whereNumber('kuis');

        Route::get('/kuis/{kuis}/hasil',[Pengajar\GradingController::class, 'results'])->whereNumber('kuis')->name('hasil');
        Route::get('/penilaian', [Pengajar\GradingController::class, 'queue'])->name('penilaian');
        Route::get('/nilai/{id}', [Pengajar\GradingController::class, 'show'])->whereNumber('id')->name('nilai');
        Route::post('/nilai/{id}', [Pengajar\GradingController::class, 'grade'])->whereNumber('id');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pengguna', [Admin\UserController::class, 'index'])->name('pengguna');
        Route::post('/pengguna', [Admin\UserController::class, 'update']);
        Route::get('/pengguna/{id}', [Admin\UserController::class, 'show'])->whereNumber('id')->name('pengguna.show');
        Route::get('/kategori', [Admin\CategoryController::class, 'index'])->name('kategori');
        Route::post('/kategori', [Admin\CategoryController::class, 'action']);
        Route::get('/kuis', [Admin\QuizController::class, 'index'])->name('kuis');
    });
});
