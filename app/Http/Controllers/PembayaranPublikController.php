<?php

namespace App\Http\Controllers;

use App\Models\Pemasukan;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Tagihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PembayaranPublikController extends Controller
{
    /**
     * GET /pembayaran?nik= — ambil tagihan belum bayar milik penghuni
     */
    public function getTagihan(Request $request): JsonResponse
    {
        $request->validate([
            'nik' => 'required|string',
        ]);

        $penghuni = Penghuni::where('nik', $request->nik)->first();

        if (! $penghuni) {
            return response()->json([
                'status' => false,
                'message' => 'Penghuni dengan NIK tersebut tidak ditemukan',
            ], 404);
        }

        $tagihan = Tagihan::where('id_penghuni', $penghuni->id)
            ->where('is_paid', false)
            ->with(['iuran', 'rumah'])
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil',
            'data' => [
                'penghuni' => $penghuni->only(['id', 'nama', 'nik']),
                'tagihan' => $tagihan,
            ],
        ]);
    }

    /**
     * POST /payment — proses pembayaran iuran (publik)
     *
     * Body: { nik, iuran: [{ id_tagihan, jangka, tagihan }] }
     */
    public function bayar(Request $request): JsonResponse
    {
        $request->validate([
            'nik' => 'required|string|exists:penghuni,nik',
            'iuran' => 'required|array|min:1',
            'iuran.*.id_tagihan' => 'required|uuid|exists:tagihan,id',
            'iuran.*.jangka' => 'required|integer|min:1',
            'iuran.*.tagihan' => 'required|integer|min:1',
        ]);

        $penghuni = Penghuni::where('nik', $request->nik)->firstOrFail();

        DB::beginTransaction();

        try {
            $ringkasan = [];

            foreach ($request->iuran as $item) {
                $tagihan = Tagihan::find($item['id_tagihan']);

                if (! $tagihan) {
                    DB::rollBack();

                    return response()->json([
                        'status' => false,
                        'message' => "Tagihan {$item['id_tagihan']} tidak ditemukan",
                    ], 404);
                }

                if ($tagihan->is_paid) {
                    DB::rollBack();

                    return response()->json([
                        'status' => false,
                        'message' => "Tagihan {$item['id_tagihan']} sudah dibayar",
                    ], 422);
                }

                // Insert pembayaran
                $pembayaran = Pembayaran::create([
                    'id_tagihan' => $tagihan->id,
                    'id_penghuni' => $penghuni->id,
                    'jumlah_bayar' => $item['tagihan'],
                    'tanggal_bayar' => Carbon::today(),
                ]);

                // Update tagihan menjadi lunas
                $tagihan->update(['is_paid' => true]);

                // Auto-insert pemasukan
                Pemasukan::create([
                    'nama' => "Pembayaran iuran {$tagihan->iuran?->nama} - {$penghuni->nama}",
                    'biaya' => $item['tagihan'],
                    'jenis' => 'pembayaran_iuran',
                    'tanggal' => Carbon::today(),
                ]);

                $ringkasan[] = [
                    'id_tagihan' => $tagihan->id,
                    'id_pembayaran' => $pembayaran->id,
                    'jumlah_bayar' => $item['tagihan'],
                ];
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Pembayaran berhasil diproses',
                'data' => [
                    'penghuni' => $penghuni->only(['id', 'nama', 'nik']),
                    'ringkasan' => $ringkasan,
                    'total_dibayar' => array_sum(array_column($ringkasan, 'jumlah_bayar')),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat memproses pembayaran',
            ], 500);
        }
    }

    /**
     * GET /payment?nik= — riwayat pembayaran penghuni
     */
    public function riwayat(Request $request): JsonResponse
    {
        $request->validate([
            'nik' => 'required|string',
        ]);

        $penghuni = Penghuni::where('nik', $request->nik)->first();

        if (! $penghuni) {
            return response()->json([
                'status' => false,
                'message' => 'Penghuni dengan NIK tersebut tidak ditemukan',
            ], 404);
        }

        $riwayat = Pembayaran::where('id_penghuni', $penghuni->id)
            ->with(['tagihan.iuran', 'tagihan.rumah'])
            ->orderByDesc('tanggal_bayar')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil',
            'data' => [
                'penghuni' => $penghuni->only(['id', 'nama', 'nik']),
                'riwayat' => $riwayat,
            ],
        ]);
    }
}
