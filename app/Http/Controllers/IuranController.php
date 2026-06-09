<?php

namespace App\Http\Controllers;

use App\Models\Iuran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IuranController extends Controller
{
    /**
     * GET /iuran
     */
    public function index(): JsonResponse
    {
        $iuran = Iuran::orderBy('nama')->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil',
            'data' => $iuran,
        ]);
    }

    /**
     * POST /iuran
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'biaya' => 'required|integer|min:1',
        ]);

        $iuran = Iuran::create($request->only(['nama', 'biaya']));

        return response()->json([
            'status' => true,
            'message' => 'Iuran berhasil ditambahkan',
            'data' => $iuran,
        ], 201);
    }

    /**
     * PATCH /iuran/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $iuran = Iuran::find($id);

        if (! $iuran) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'nama' => 'sometimes|string|max:100',
            'biaya' => 'sometimes|integer|min:1',
        ]);

        $iuran->update($request->only(['nama', 'biaya']));

        return response()->json([
            'status' => true,
            'message' => 'Iuran berhasil diperbarui',
            'data' => $iuran->fresh(),
        ]);
    }

    /**
     * DELETE /iuran/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $iuran = Iuran::find($id);

        if (! $iuran) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $hasTagihan = $iuran->tagihan()->exists();
        if ($hasTagihan) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak dapat menghapus iuran yang sudah memiliki tagihan',
            ], 422);
        }

        $iuran->delete();

        return response()->json([
            'status' => true,
            'message' => 'Iuran berhasil dihapus',
        ]);
    }
}
