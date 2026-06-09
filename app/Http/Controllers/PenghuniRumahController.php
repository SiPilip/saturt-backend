<?php

namespace App\Http\Controllers;

use App\Models\PenghuniRumah;
use App\Models\Rumah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenghuniRumahController extends Controller
{
    /**
     * POST /rumah/{id}/penghuni — assign penghuni ke rumah
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $rumah = Rumah::find($id);

        if (! $rumah) {
            return response()->json([
                'status' => false,
                'message' => 'Rumah tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'id_penghuni' => 'required|uuid|exists:penghuni,id',
            'tanggal_masuk' => 'required|date',
        ]);

        // Cek apakah penghuni sudah aktif di rumah lain
        $sudahAktif = PenghuniRumah::where('id_penghuni', $request->id_penghuni)
            ->whereNull('tanggal_keluar')
            ->exists();

        if ($sudahAktif) {
            return response()->json([
                'status' => false,
                'message' => 'Penghuni masih aktif di rumah lain',
            ], 422);
        }

        $penghuniRumah = PenghuniRumah::create([
            'id_penghuni' => $request->id_penghuni,
            'id_rumah' => $id,
            'tanggal_masuk' => $request->tanggal_masuk,
            'tanggal_keluar' => null,
        ]);

        // Update status rumah menjadi terisi
        $rumah->update(['is_filled' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Penghuni berhasil ditambahkan ke rumah',
            'data' => $penghuniRumah->load('penghuni'),
        ], 201);
    }

    /**
     * PATCH /rumah/{id}/penghuni/{id_penghuni} — checkout penghuni dari rumah
     */
    public function update(Request $request, string $id, string $idPenghuni): JsonResponse
    {
        $penghuniRumah = PenghuniRumah::where('id_rumah', $id)
            ->where('id_penghuni', $idPenghuni)
            ->whereNull('tanggal_keluar')
            ->first();

        if (! $penghuniRumah) {
            return response()->json([
                'status' => false,
                'message' => 'Data penghuni aktif di rumah ini tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'tanggal_keluar' => 'required|date|after_or_equal:'.$penghuniRumah->tanggal_masuk->format('Y-m-d'),
        ]);

        $penghuniRumah->update(['tanggal_keluar' => $request->tanggal_keluar]);

        // Cek apakah masih ada penghuni aktif di rumah ini
        $masihAdaPenghuni = PenghuniRumah::where('id_rumah', $id)
            ->whereNull('tanggal_keluar')
            ->exists();

        Rumah::where('id', $id)->update(['is_filled' => $masihAdaPenghuni]);

        return response()->json([
            'status' => true,
            'message' => 'Penghuni berhasil checkout dari rumah',
            'data' => $penghuniRumah->fresh()->load('penghuni'),
        ]);
    }
}
