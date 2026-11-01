<?php

use App\Http\Controllers\SchoolAdmin;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Family;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Teacher;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// ---------- Tamu ----------
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1');

    // Lupa dan atur ulang kata sandi.
    // Nama route (password.*) dipakai oleh Laravel saat membuat tautan di email.
    Route::get('/lupa-kata-sandi', [PasswordResetController::class, 'showForgot'])
        ->name('password.request');
    Route::post('/lupa-kata-sandi', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('/atur-ulang-kata-sandi/{token}', [PasswordResetController::class, 'showReset'])
        ->name('password.reset');
    Route::post('/atur-ulang-kata-sandi', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')->name('password.update');
});

// ---------- Harus login ----------
Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
        // Ganti kata sandi wajib (setelah memakai kata sandi sementara)
    Route::get('/ganti-kata-sandi', [PasswordChangeController::class, 'edit'])->name('password.force');
    Route::post('/ganti-kata-sandi', [PasswordChangeController::class, 'update'])
        ->middleware('throttle:10,1')->name('password.force.update');
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    Route::get('/papan-peringkat', [LeaderboardController::class, 'index'])->name('leaderboard');

    // ----- Siswa -----
    Route::middleware('role:student')->prefix('siswa')->name('student.')->group(function () {
        Route::get('/', [Student\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/kelas/gabung', [Student\DashboardController::class, 'joinClass'])
            ->name('class.join');

        // PERBAIKAN: route ini sebelumnya belum ada
        Route::post('/sekolah', [Student\DashboardController::class, 'updateSchool'])
            ->name('school.update');

        Route::get('/skenario', [Student\ScenarioController::class, 'index'])
            ->name('scenarios.index');
        Route::get('/skenario/{scenario:slug}', [Student\ScenarioController::class, 'show'])
            ->name('scenarios.show');
        Route::post('/skenario/{scenario:slug}/mulai', [Student\ScenarioController::class, 'start'])
            ->name('scenarios.start');
        Route::post('/skenario/{scenario:slug}/ulang', [Student\ScenarioController::class, 'restart'])
            ->name('scenarios.restart');
        Route::get('/main/{attempt}', [Student\ScenarioController::class, 'play'])->name('play');
        Route::post('/main/{attempt}/pilih/{choice}', [Student\ScenarioController::class, 'choose'])
            ->name('choose');
        Route::get('/hasil/{attempt}', [Student\ScenarioController::class, 'result'])
            ->name('result');

        Route::get('/belajar', [Student\LessonController::class, 'index'])->name('lessons.index');
        Route::get('/belajar/{lesson:slug}', [Student\LessonController::class, 'show'])
            ->name('lessons.show');
        Route::post('/belajar/{lesson:slug}/selesai', [Student\LessonController::class, 'complete'])
            ->name('lessons.complete');

        Route::get('/aksi', [Student\ActionController::class, 'index'])->name('actions.index');
        Route::post('/aksi/{action}/selesai', [Student\ActionController::class, 'complete'])
            ->name('actions.complete');

        Route::get('/lencana', [Student\BadgeController::class, 'index'])->name('badges');
    });

    // ----- Orang tua -----
    Route::middleware('role:parent')->prefix('orang-tua')->name('family.')->group(function () {
        Route::get('/', [Family\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/hubungkan', [Family\DashboardController::class, 'link'])->name('link');
        Route::get('/anak/{child}', [Family\DashboardController::class, 'show'])->name('child');
        Route::delete('/anak/{child}', [Family\DashboardController::class, 'unlink'])
            ->name('unlink');
    });

    // ----- Guru -----
    Route::middleware('role:teacher')->prefix('guru')->name('teacher.')->group(function () {
        Route::get('/', [Teacher\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/kelas', [Teacher\DashboardController::class, 'store'])->name('classes.store');
        Route::get('/kelas/{classroom}', [Teacher\DashboardController::class, 'show'])
            ->name('classes.show');
        Route::get('/kelas/{classroom}/siswa/{student}', [Teacher\DashboardController::class, 'student'])
            ->name('classes.student');
    });

        // ----- Admin sekolah -----
    Route::middleware('role:school_admin')->prefix('admin-sekolah')->name('school.')->group(function () {
        Route::get('/', [SchoolAdmin\DashboardController::class, 'index'])->name('dashboard');
    });

    // ----- Pengguna (superadmin: semua sekolah, admin sekolah: sekolahnya saja) -----
    Route::middleware('role:superadmin,school_admin')->prefix('pengguna')->name('users.')->group(function () {
        Route::get('/', [Admin\UserController::class, 'index'])->name('index');
        Route::post('/{user}/sandi-sementara', [Admin\UserController::class, 'tempPassword'])
            ->middleware('throttle:20,1')->name('temp-password');
        Route::post('/{user}/nonaktifkan', [Admin\UserController::class, 'deactivate'])->name('deactivate');
        Route::post('/{user}/aktifkan', [Admin\UserController::class, 'activate'])->name('activate');
    });
    // ----- Admin -----
        Route::middleware('role:superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        

        // Sekolah
        Route::get('/sekolah', [Admin\SchoolController::class, 'index'])->name('schools.index');
        Route::put('/sekolah/{school}', [Admin\SchoolController::class, 'update'])->name('schools.update');
        Route::post('/sekolah/{school}/gabung', [Admin\SchoolController::class, 'merge'])->name('schools.merge');
        Route::delete('/sekolah/{school}', [Admin\SchoolController::class, 'destroy'])->name('schools.destroy');
       
                // Akun admin sekolah (hanya superadmin)
        Route::get('/admin-sekolah/baru', [Admin\SchoolAdminController::class, 'create'])->name('school-admins.create');
        Route::post('/admin-sekolah', [Admin\SchoolAdminController::class, 'store'])->name('school-admins.store');
                // Hapus permanen (hanya superadmin, hanya untuk akun yang sudah dinonaktifkan)
        Route::get('/pengguna/{user}/hapus', [Admin\UserController::class, 'confirmDelete'])->name('users.confirm-delete');
        Route::delete('/pengguna/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

        // Skenario
        Route::get('/skenario', [Admin\ScenarioController::class, 'index'])->name('scenarios.index');
        Route::get('/skenario/baru', [Admin\ScenarioController::class, 'create'])->name('scenarios.create');
        Route::post('/skenario', [Admin\ScenarioController::class, 'store'])->name('scenarios.store');
        Route::get('/skenario/{scenario}/ubah', [Admin\ScenarioController::class, 'edit'])->name('scenarios.edit');
        Route::put('/skenario/{scenario}', [Admin\ScenarioController::class, 'update'])->name('scenarios.update');
        Route::delete('/skenario/{scenario}', [Admin\ScenarioController::class, 'destroy'])->name('scenarios.destroy');
        Route::post('/skenario/{scenario}/publikasi', [Admin\ScenarioController::class, 'toggle'])->name('scenarios.toggle');

        // Kategori
        Route::get('/kategori', [Admin\CategoryController::class, 'index'])->name('categories.index');
        Route::get('/kategori/baru', [Admin\CategoryController::class, 'create'])->name('categories.create');
        Route::post('/kategori', [Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::get('/kategori/{category}/ubah', [Admin\CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/kategori/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/kategori/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

        // Lencana
        Route::get('/lencana', [Admin\BadgeController::class, 'index'])->name('badges.index');
        Route::get('/lencana/baru', [Admin\BadgeController::class, 'create'])->name('badges.create');
        Route::post('/lencana', [Admin\BadgeController::class, 'store'])->name('badges.store');
        Route::post('/lencana/periksa-ulang', [Admin\BadgeController::class, 'regrant'])->name('badges.regrant');
        Route::get('/lencana/{badge}/ubah', [Admin\BadgeController::class, 'edit'])->name('badges.edit');
        Route::put('/lencana/{badge}', [Admin\BadgeController::class, 'update'])->name('badges.update');
        Route::delete('/lencana/{badge}', [Admin\BadgeController::class, 'destroy'])->name('badges.destroy');

        // Adegan
        Route::post('/skenario/{scenario}/adegan', [Admin\NodeController::class, 'store'])->name('nodes.store');
        Route::put('/adegan/{node}', [Admin\NodeController::class, 'update'])->name('nodes.update');
        Route::delete('/adegan/{node}', [Admin\NodeController::class, 'destroy'])->name('nodes.destroy');

        // Pilihan
        Route::post('/adegan/{node}/pilihan', [Admin\ChoiceController::class, 'store'])->name('choices.store');
        Route::put('/pilihan/{choice}', [Admin\ChoiceController::class, 'update'])->name('choices.update');
        Route::delete('/pilihan/{choice}', [Admin\ChoiceController::class, 'destroy'])->name('choices.destroy');

        // Materi belajar
Route::get('/belajar', [Admin\LessonController::class, 'index'])->name('lessons.index');
Route::get('/belajar/baru', [Admin\LessonController::class, 'create'])->name('lessons.create');
Route::post('/belajar/pratinjau', [Admin\LessonController::class, 'preview'])->name('lessons.preview');
Route::post('/belajar', [Admin\LessonController::class, 'store'])->name('lessons.store');
Route::get('/belajar/{lesson}/ubah', [Admin\LessonController::class, 'edit'])->name('lessons.edit');
Route::put('/belajar/{lesson}', [Admin\LessonController::class, 'update'])->name('lessons.update');
Route::delete('/belajar/{lesson}', [Admin\LessonController::class, 'destroy'])->name('lessons.destroy');

// Aksi keamanan
Route::get('/aksi', [Admin\ActionController::class, 'index'])->name('actions.index');
Route::get('/aksi/baru', [Admin\ActionController::class, 'create'])->name('actions.create');
Route::post('/aksi', [Admin\ActionController::class, 'store'])->name('actions.store');
Route::get('/aksi/{action}/ubah', [Admin\ActionController::class, 'edit'])->name('actions.edit');
Route::put('/aksi/{action}', [Admin\ActionController::class, 'update'])->name('actions.update');
Route::delete('/aksi/{action}', [Admin\ActionController::class, 'destroy'])->name('actions.destroy');
    });
});