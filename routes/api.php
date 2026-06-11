<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IuranController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\KeuanganExportController;
use App\Http\Controllers\PemasukanController;
use App\Http\Controllers\PembayaranPublikController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PenghuniController;
use App\Http\Controllers\PenghuniRumahController;
use App\Http\Controllers\RumahController;
use App\Http\Controllers\TagihanController;
use Illuminate\Support\Facades\Route;

// ─── ROOT ─────────────────────────────────────────────────────────────────────
Route::get('/', fn () => response()->json(['app' => 'saturt', 'version' => '1.0.0']));

// ─── PUBLIC — Pembayaran Iuran (tanpa auth) ───────────────────────────────────
Route::get('/pembayaran', [PembayaranPublikController::class, 'getTagihan']);
Route::post('/payment', [PembayaranPublikController::class, 'bayar']);
Route::get('/payment', [PembayaranPublikController::class, 'riwayat']);

// ─── AUTH ─────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
});

// ─── PROTECTED (JWT) ──────────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/grafik', [DashboardController::class, 'grafik']);
        Route::get('/penghuni-belum-bayar', [DashboardController::class, 'penghuniBelumBayar']);
        Route::get('/pembayaran-terakhir', [DashboardController::class, 'pembayaranTerakhir']);
    });

    // Penghuni
    Route::prefix('penghuni')->group(function () {
        Route::get('/pagination', [PenghuniController::class, 'index']);
        Route::post('/', [PenghuniController::class, 'store']);
        Route::patch('/{id}', [PenghuniController::class, 'update']);
        Route::delete('/{id}', [PenghuniController::class, 'destroy']);
        Route::get('/{id}/riwayat-pembayaran/pagination', [PenghuniController::class, 'riwayatPembayaran']);
        Route::get('/{id}/tagihan/pagination', [PenghuniController::class, 'tagihan']);
    });

    // Rumah
    Route::prefix('rumah')->group(function () {
        Route::get('/pagination', [RumahController::class, 'index']);
        Route::post('/', [RumahController::class, 'store']);
        Route::patch('/{id}', [RumahController::class, 'update']);
        Route::delete('/{id}', [RumahController::class, 'destroy']);
        Route::get('/{id}/riwayat-penghuni/pagination', [RumahController::class, 'riwayatPenghuni']);
        Route::get('/{id}/riwayat-pembayaran/pagination', [RumahController::class, 'riwayatPembayaran']);
        Route::post('/{id}/penghuni', [PenghuniRumahController::class, 'store']);
        Route::patch('/{id}/penghuni/{idPenghuni}', [PenghuniRumahController::class, 'update']);
    });

    // Iuran
    Route::prefix('iuran')->group(function () {
        Route::get('/pagination', [IuranController::class, 'index']);
        Route::post('/', [IuranController::class, 'store']);
        Route::patch('/{id}', [IuranController::class, 'update']);
        Route::delete('/{id}', [IuranController::class, 'destroy']);
    });

    // Tagihan
    Route::prefix('tagihan')->group(function () {
        Route::get('/', [TagihanController::class, 'index']);
        Route::post('/generate', [TagihanController::class, 'generate']);
        Route::delete('/{id}', [TagihanController::class, 'destroy']);
    });

    // Keuangan
    Route::prefix('keuangan')->group(function () {
        Route::get('/pemasukan/pagination', [PemasukanController::class, 'index']);
        Route::post('/pemasukan', [PemasukanController::class, 'store']);
        Route::patch('/pemasukan/{id}', [PemasukanController::class, 'update']);
        Route::delete('/pemasukan/{id}', [PemasukanController::class, 'destroy']);

        Route::get('/pengeluaran/pagination', [PengeluaranController::class, 'index']);
        Route::post('/pengeluaran', [PengeluaranController::class, 'store']);
        Route::patch('/pengeluaran/{id}', [PengeluaranController::class, 'update']);
        Route::delete('/pengeluaran/{id}', [PengeluaranController::class, 'destroy']);

        Route::get('/ringkasan', [KeuanganController::class, 'ringkasan']);
        Route::get('/export', [KeuanganExportController::class, 'export']);
    });
});
