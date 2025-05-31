<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

// Главная страница и SPA маршруты - отображение Vue SPA
Route::get('/', function () {
    return view('app');
});

// Маршрут для админ-панели SPA
Route::get('/admin', function () {
    return view('app');
});

// Маршруты аутентификации через Blade
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// API для проверки статуса аутентификации (используется Vue SPA)
Route::get('/api/auth/status', [AuthController::class, 'getAuthStatus']);


// Существующие API маршруты для форума (используются Vue SPA)
Route::prefix('forum')->group(function () {
    Route::get('/initial-data', [ForumController::class, 'getInitialData']);
    // Удаляем дублирующийся маршрут и добавляем middleware auth ко всем маршрутам изменения данных
    Route::post('/topics', [ForumController::class, 'createTopic'])->middleware('auth');
    Route::put('/topics/{id}', [ForumController::class, 'updateTopic'])->middleware('auth');
    Route::get('/topics/{id}', [ForumController::class, 'getTopic']);
    Route::delete('/topics/{id}', [ForumController::class, 'deleteTopic'])->middleware('auth');
    Route::post('/topics/{id}/replies', [ForumController::class, 'addReply'])->middleware('auth');
    Route::put('/replies/{id}', [ForumController::class, 'updateReply'])->middleware('auth');
    Route::delete('/replies/{id}', [ForumController::class, 'deleteReply'])->middleware('auth');
});

// Маршруты администратора
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // Управление пользователями
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::post('/users/{id}/toggle-block', [AdminController::class, 'toggleUserBlock']);
    
    // Управление темами
    Route::put('/topics/{id}', [AdminController::class, 'updateTopic']);
    Route::delete('/topics/{id}', [AdminController::class, 'deleteTopic']);
    
    // Управление ответами
    Route::put('/replies/{id}', [AdminController::class, 'updateReply']);
    Route::delete('/replies/{id}', [AdminController::class, 'deleteReply']);
    
    // Админ-панель
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
});