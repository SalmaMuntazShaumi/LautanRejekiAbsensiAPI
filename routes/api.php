
<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TimeOffController;

// ✅ PUBLIC (tanpa login)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

// 🔐 PROTECTED (harus login)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'user']);
    Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/history', [AttendanceController::class, 'userHistory']);
    Route::post('/profile/update', [UserController::class, 'updateProfile']);
    Route::post('/time-off', [TimeOffController::class, 'store']);
    Route::get('/time-off', [TimeOffController::class, 'index']);  
    
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/history', [AttendanceController::class, 'history']);
        Route::post('/time-off/{id}/approve', [TimeOffController::class, 'approve']);
        Route::get('/time-off/all', [TimeOffController::class, 'adminIndex']);
        Route::post('/time-off/{id}/reject', [TimeOffController::class, 'reject']);
    });
});