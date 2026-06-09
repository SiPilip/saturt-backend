<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class KeuanganController extends Controller
{
    /**
     * GET /keuangan/ringkasan
     *
     * Ringkasan keuangan: total pemasukan, pengeluaran, saldo bulan ini dan all-time.
     */
    public function ringkasan(): JsonResponse
    {
        $bulanIni = Carbon::now()->month;
        $tahunIni = Carbon::now()->year;

        // Bulan ini
        $pemasukanBulanIni = DB::table('pemasukan')
            ->whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->sum('biaya');

        $pengeluaranBulanIni = DB::table('pengeluaran')
            ->whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->sum('biaya');

        // All-time
        $totalPemasukan = DB::table('pemasukan')->sum('biaya');
        $totalPengeluaran = DB::table('pengeluaran')->sum('biaya');

        // Tagihan bulan ini
        $totalTagihanBulanIni = DB::table('tagihan')
            ->where('bulan', $bulanIni)
            ->where('tahun', $tahunIni)
            ->count();

        $tagihanLunasBulanIni = DB::table('tagihan')
            ->where('bulan', $bulanIni)
            ->where('tahun', $tahunIni)
            ->where('is_paid', true)
            ->count();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil',
            'data' => [
                'bulan_ini' => [
                    'pemasukan' => (int) $pemasukanBulanIni,
                    'pengeluaran' => (int) $pengeluaranBulanIni,
                    'saldo' => (int) ($pemasukanBulanIni - $pengeluaranBulanIni),
                    'tagihan_total' => $totalTagihanBulanIni,
                    'tagihan_lunas' => $tagihanLunasBulanIni,
                    'tagihan_belum_bayar' => $totalTagihanBulanIni - $tagihanLunasBulanIni,
                ],
                'all_time' => [
                    'total_pemasukan' => (int) $totalPemasukan,
                    'total_pengeluaran' => (int) $totalPengeluaran,
                    'saldo' => (int) ($totalPemasukan - $totalPengeluaran),
                ],
            ],
        ]);
    }
}
