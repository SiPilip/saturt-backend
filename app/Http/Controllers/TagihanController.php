<?php

namespace App\Http\Controllers;

use App\Models\Iuran;
use App\Models\PenghuniRumah;
use App\Models\Rumah;
use App\Models\Tagihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagihanController extends Controller
{
    /**
     * GET /tagihan
     */
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 15));

        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        
        $validSortColumns = ['tahun', 'bulan', 'created_at', 'is_paid', 'nominal'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'created_at';
        }

        $query = Tagihan::with(['rumah', 'iuran', 'penghuni', 'pembayaran']);

        if ($request->has('bulan')) {
            $query->where('bulan', $request->query('bulan'));
        }
        if ($request->has('tahun')) {
            $query->where('tahun', $request->query('tahun'));
        }
        if ($request->has('is_paid')) {
            $query->where('is_paid', filter_var($request->query('is_paid'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->has('id_rumah')) {
            $query->where('id_rumah', $request->query('id_rumah'));
        }

        if ($sortBy === 'created_at') {
             $query->orderByDesc('tahun')->orderByDesc('bulan');
        } else {
             $query->orderBy($sortBy, $sortDir);
        }

        $total = $query->count();
        $data = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil',
            'data' => $data,
            'meta' => [
                'total' => $total,
                'total_pages' => (int) ceil($total / $limit),
                'page' => $page,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * POST /tagihan/generate
     *
     * Generate tagihan untuk semua rumah yang terisi × semua iuran.
     * Menggunakan insertOrIgnore untuk mencegah duplikasi (berdasarkan unique constraint).
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $rumahTerisi = Rumah::where('is_filled', true)->get();
        $semuaIuran = Iuran::all();

        if ($rumahTerisi->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada rumah yang terisi saat ini',
            ], 422);
        }

        if ($semuaIuran->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada iuran yang terdaftar',
            ], 422);
        }

        $created = 0;
        $skipped = 0;

        foreach ($rumahTerisi as $rumah) {
            // Ambil penghuni aktif untuk rumah ini
            $penghuniAktif = PenghuniRumah::where('id_rumah', $rumah->id)
                ->whereNull('tanggal_keluar')
                ->first();

            foreach ($semuaIuran as $iuran) {
                // Cek apakah tagihan sudah ada
                $exists = Tagihan::where('id_rumah', $rumah->id)
                    ->where('id_iuran', $iuran->id)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                Tagihan::create([
                    'id_rumah' => $rumah->id,
                    'id_iuran' => $iuran->id,
                    'id_penghuni' => $penghuniAktif?->id_penghuni,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'nominal' => $iuran->biaya,
                    'is_paid' => false,
                ]);

                $created++;
            }
        }

        return response()->json([
            'status' => true,
            'message' => "Tagihan berhasil digenerate: {$created} dibuat, {$skipped} dilewati (sudah ada)",
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
        ]);
    }

    /**
     * DELETE /tagihan/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $tagihan = Tagihan::find($id);

        if (! $tagihan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        if ($tagihan->is_paid) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak dapat menghapus tagihan yang sudah dibayar',
            ], 422);
        }

        $tagihan->delete();

        return response()->json([
            'status' => true,
            'message' => 'Tagihan berhasil dihapus',
        ]);
    }
}
