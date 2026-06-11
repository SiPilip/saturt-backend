<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /dashboard/grafik?range=1|3|6|12
     *
     * Mengembalikan data pemasukan dan pengeluaran per bulan dalam range bulan terakhir.
     */
    public function grafik(Request $request): JsonResponse
    {
        $range = (int) $request->query('range', 6);
        $range = in_array($range, [1, 3, 6, 12]) ? $range : 6;

        $bulanAwal = Carbon::now()->subMonths($range - 1)->startOfMonth();

        // Pemasukan per bulan (dari tabel pemasukan)
        $pemasukan = DB::table('pemasukan')
            ->where('tanggal', '>=', $bulanAwal)
            ->selectRaw('YEAR(tanggal) as tahun, MONTH(tanggal) as bulan, SUM(biaya) as total')
            ->groupByRaw('YEAR(tanggal), MONTH(tanggal)')
            ->orderByRaw('YEAR(tanggal), MONTH(tanggal)')
            ->get()
            ->keyBy(fn ($row) => "{$row->tahun}-{$row->bulan}");

        // Pengeluaran per bulan
        $pengeluaran = DB::table('pengeluaran')
            ->where('tanggal', '>=', $bulanAwal)
            ->selectRaw('YEAR(tanggal) as tahun, MONTH(tanggal) as bulan, SUM(biaya) as total')
            ->groupByRaw('YEAR(tanggal), MONTH(tanggal)')
            ->orderByRaw('YEAR(tanggal), MONTH(tanggal)')
            ->get()
            ->keyBy(fn ($row) => "{$row->tahun}-{$row->bulan}");

        // Bangun array per bulan dalam range
        $labels = [];
        $dataPemasukan = [];
        $dataPengeluaran = [];

        for ($i = $range - 1; $i >= 0; $i--) {
            $bulan = Carbon::now()->subMonths($i);
            $key = "{$bulan->year}-{$bulan->month}";
            $labels[] = $bulan->format('M Y');
            $dataPemasukan[] = $pemasukan->get($key)?->total ?? 0;
            $dataPengeluaran[] = $pengeluaran->get($key)?->total ?? 0;
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil',
            'data' => [
                'labels' => $labels,
                'pemasukan' => $dataPemasukan,
                'pengeluaran' => $dataPengeluaran,
            ],
        ]);
    }

    /**
     * GET /dashboard/penghuni-belum-bayar
     *
     * Penghuni dengan tagihan is_paid=false bulan ini, limit 10.
     */
    public function penghuniBelumBayar(Request $request): JsonResponse
    {
        $bulanIni = Carbon::now()->month;
        $tahunIni = Carbon::now()->year;

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 5));

        $query = Tagihan::where('is_paid', false)
            ->where('bulan', $bulanIni)
            ->where('tahun', $tahunIni)
            ->with(['penghuni', 'rumah', 'iuran']);

        $total = $query->count();
        $tagihan = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil',
            'data' => $tagihan,
            'meta' => [
                'total' => $total,
                'total_pages' => (int) ceil($total / $limit),
                'page' => $page,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * GET /dashboard/pembayaran-terakhir
     *
     * 10 pembayaran terakhir.
     */
    public function pembayaranTerakhir(): JsonResponse
    {
        $pembayaran = Pembayaran::with(['penghuni', 'tagihan.iuran', 'tagihan.rumah'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil',
            'data' => $pembayaran,
        ]);
    }
}
