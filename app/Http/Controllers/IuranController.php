<?php

namespace App\Http\Controllers;

use App\Models\Iuran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IuranController extends Controller
{
    /**
     * GET /iuran/pagination
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $search = $request->query('search', '');
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');

        $allowedSorts = ['nama', 'biaya', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $query = Iuran::query();

        if ($search) {
            $query->where('nama', 'like', "%{$search}%");
        }

        $query->orderBy($sortBy, $sortDir);
        $iuran = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data Iuran',
            'data' => $iuran->items(),
            'meta' => [
                'total' => $iuran->total(),
                'total_pages' => $iuran->lastPage(),
                'page' => $iuran->currentPage(),
                'limit' => $iuran->perPage(),
            ],
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
