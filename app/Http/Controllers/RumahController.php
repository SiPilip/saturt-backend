<?php

namespace App\Http\Controllers;

use App\Models\Rumah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RumahController extends Controller
{
    /**
     * GET /rumah/pagination
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 15));

        $query = Rumah::with(['penghuniAktif.penghuni']);

        if ($search) {
            $query->where('blok_nomor', 'like', "%{$search}%");
        }

        $total = $query->count();
        $data = $query->orderBy('blok_nomor')->skip(($page - 1) * $limit)->take($limit)->get();

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
     * POST /rumah
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'blok_nomor' => 'required|string|max:20|unique:rumah,blok_nomor',
            'is_filled' => 'boolean',
        ]);

        $rumah = Rumah::create([
            'blok_nomor' => $request->blok_nomor,
            'is_filled' => $request->boolean('is_filled', false),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Rumah berhasil ditambahkan',
            'data' => $rumah,
        ], 201);
    }

    /**
     * PATCH /rumah/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $rumah = Rumah::find($id);

        if (! $rumah) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'blok_nomor' => 'sometimes|string|max:20|unique:rumah,blok_nomor,'.$id,
            'is_filled' => 'sometimes|boolean',
        ]);

        $rumah->update($request->only(['blok_nomor', 'is_filled']));

        return response()->json([
            'status' => true,
            'message' => 'Rumah berhasil diperbarui',
            'data' => $rumah->fresh(),
        ]);
    }

    /**
     * DELETE /rumah/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $rumah = Rumah::find($id);

        if (! $rumah) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $hasTagihanAktif = $rumah->tagihan()->where('is_paid', false)->exists();
        if ($hasTagihanAktif) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak dapat menghapus rumah yang masih memiliki tagihan aktif',
            ], 422);
        }

        $rumah->delete();

        return response()->json([
            'status' => true,
            'message' => 'Rumah berhasil dihapus',
        ]);
    }

    /**
     * GET /rumah/{id}/riwayat-penghuni/pagination
     */
    public function riwayatPenghuni(Request $request, string $id): JsonResponse
    {
        $rumah = Rumah::find($id);

        if (! $rumah) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 15));

        $query = $rumah->penghuniRumah()->with('penghuni')->orderByDesc('tanggal_masuk');

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
     * GET /rumah/{id}/riwayat-pembayaran/pagination
     */
    public function riwayatPembayaran(Request $request, string $id): JsonResponse
    {
        $rumah = Rumah::find($id);

        if (! $rumah) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 15));

        $query = $rumah->tagihan()->whereHas('pembayaran')
            ->with(['pembayaran', 'iuran', 'penghuni'])
            ->orderByDesc('tahun')->orderByDesc('bulan');

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
}
