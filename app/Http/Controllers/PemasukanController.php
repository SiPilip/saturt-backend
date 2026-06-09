<?php

namespace App\Http\Controllers;

use App\Models\Pemasukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemasukanController extends Controller
{
    /**
     * GET /keuangan/pemasukan/pagination
     */
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 15));
        $search = $request->query('search');

        $query = Pemasukan::query();

        if ($search) {
            $query->where('nama', 'like', "%{$search}%");
        }
        if ($request->has('jenis')) {
            $query->where('jenis', $request->query('jenis'));
        }

        $total = $query->count();
        $data = $query->orderByDesc('tanggal')->skip(($page - 1) * $limit)->take($limit)->get();

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
     * POST /keuangan/pemasukan
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'biaya' => 'required|integer|min:1',
            'jenis' => 'required|in:pembayaran_iuran,donasi,hibah,lainnya',
            'tanggal' => 'required|date',
        ]);

        $pemasukan = Pemasukan::create($request->only(['nama', 'biaya', 'jenis', 'tanggal']));

        return response()->json([
            'status' => true,
            'message' => 'Pemasukan berhasil ditambahkan',
            'data' => $pemasukan,
        ], 201);
    }

    /**
     * PATCH /keuangan/pemasukan/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $pemasukan = Pemasukan::find($id);

        if (! $pemasukan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'nama' => 'sometimes|string|max:255',
            'biaya' => 'sometimes|integer|min:1',
            'jenis' => 'sometimes|in:pembayaran_iuran,donasi,hibah,lainnya',
            'tanggal' => 'sometimes|date',
        ]);

        $pemasukan->update($request->only(['nama', 'biaya', 'jenis', 'tanggal']));

        return response()->json([
            'status' => true,
            'message' => 'Pemasukan berhasil diperbarui',
            'data' => $pemasukan->fresh(),
        ]);
    }

    /**
     * DELETE /keuangan/pemasukan/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $pemasukan = Pemasukan::find($id);

        if (! $pemasukan) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $pemasukan->delete();

        return response()->json([
            'status' => true,
            'message' => 'Pemasukan berhasil dihapus',
        ]);
    }
}
