<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    /**
     * GET /keuangan/pengeluaran/pagination
     */
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 15));
        $search = $request->query('search');
        
        $sortBy = $request->query('sort_by', 'tanggal');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        
        $validSortColumns = ['tanggal', 'biaya', 'nama', 'jenis', 'created_at'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'tanggal';
        }

        $query = Pengeluaran::query();

        if ($search) {
            $query->where('nama', 'like', "%{$search}%");
        }
        if ($request->has('jenis')) {
            $query->where('jenis', $request->query('jenis'));
        }

        $total = $query->count();
        $data = $query->orderBy($sortBy, $sortDir)->skip(($page - 1) * $limit)->take($limit)->get();

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
     * POST /keuangan/pengeluaran
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'biaya' => 'required|integer|min:1',
            'jenis' => 'required|in:operasional,perbaikan,gaji,lainnya',
            'tanggal' => 'required|date',
        ]);

        $pengeluaran = Pengeluaran::create($request->only(['nama', 'biaya', 'jenis', 'tanggal']));

        return response()->json([
            'status' => true,
            'message' => 'Pengeluaran berhasil ditambahkan',
            'data' => $pengeluaran,
        ], 201);
    }

    /**
     * PATCH /keuangan/pengeluaran/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $pengeluaran = Pengeluaran::find($id);

        if (! $pengeluaran) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'nama' => 'sometimes|string|max:255',
            'biaya' => 'sometimes|integer|min:1',
            'jenis' => 'sometimes|in:operasional,perbaikan,gaji,lainnya',
            'tanggal' => 'sometimes|date',
        ]);

        $pengeluaran->update($request->only(['nama', 'biaya', 'jenis', 'tanggal']));

        return response()->json([
            'status' => true,
            'message' => 'Pengeluaran berhasil diperbarui',
            'data' => $pengeluaran->fresh(),
        ]);
    }

    /**
     * DELETE /keuangan/pengeluaran/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $pengeluaran = Pengeluaran::find($id);

        if (! $pengeluaran) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $pengeluaran->delete();

        return response()->json([
            'status' => true,
            'message' => 'Pengeluaran berhasil dihapus',
        ]);
    }
}
