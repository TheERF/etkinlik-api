<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TicketController;

//Authentication İşlemleri
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
});

Route::get('/test-admin', function () {
    return response()->json(['message' => 'Admin erişimi başarılı']);
})->middleware('is_admin');


Route::middleware(['auth:api'])->group(function () {
    Route::post('/events', [EventController::class, 'store'])->middleware('is_admin');
    Route::put('/events/{id}', [EventController::class, 'update'])->middleware('is_admin');
    Route::delete('/events/{id}', [EventController::class, 'destroy'])->middleware('is_admin');
});

//Etkinlik İşlemleri (Events)
Route::middleware(['auth:api'])->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);
});


//Koltuk İşlemleri (Seats)
Route::middleware(['auth:api'])->prefix('seats')->group(function () {
    Route::get('/events/{id}', [SeatController::class, 'getSeatsByEvent']);
    Route::get('/venues/{id}', [SeatController::class, 'getSeatsByVenue']);
    Route::post('/block', [SeatController::class, 'blockSeats']);
    Route::delete('/release', [SeatController::class, 'releaseSeats']);
});

//Rezervasyon İşlemleri (Reservations)
Route::middleware(['auth:api'])->prefix('reservations')->group(function () {
    Route::post('/', [ReservationController::class, 'store']); // Yeni rezervasyon
    Route::get('/', [ReservationController::class, 'index']); // Kullanıcının rezervasyonlarını getir
    Route::get('/{id}', [ReservationController::class, 'show']); // Belirli rezervasyonu getir
    Route::post('/{id}/confirm', [ReservationController::class, 'confirm']); // Rezervasyonu onayla
    Route::delete('/{id}', [ReservationController::class, 'destroy']); // Rezervasyonu iptal et
});

//Bilet İşlemleri (Tickets)
Route::middleware(['auth:api'])->prefix('tickets')->group(function () {
    Route::get('/', [TicketController::class, 'index']); // Kullanıcının tüm biletlerini getir
    Route::get('/{id}', [TicketController::class, 'show']); // Belirli bir bileti getir
    Route::get('/{id}/download', [TicketController::class, 'download']); // Bileti PDF olarak indir
    Route::post('/{id}/transfer', [TicketController::class, 'transfer']); // Bileti başka kullanıcıya transfer et
    Route::post('/{id}/cancel', [TicketController::class, 'cancelTicket']); // Bileti iptal et (Etkinlikten 24 saat önce)
});
