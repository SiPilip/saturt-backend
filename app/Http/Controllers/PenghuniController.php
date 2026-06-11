<?php

namespace App\Http\Controllers;

use App\Models\Penghuni;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class PenghuniController extends Controller
{
    /**
     * GET /penghuni/pagination
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 15));

        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        
        // Whitelist kolom yang bisa disortir untuk keamanan
        $validSortColumns = ['nama', 'nik', 'created_at', 'status_penghuni', 'telephone', 'is_menikah'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'created_at';
        }

        $query = Penghuni::with(['penghuniRumah' => function ($q) {
            $q->whereNull('tanggal_keluar')->with('rumah');
        }])->withCount(['tagihan as tagihan_belum_bayar_count' => function ($q) {
            $q->where('is_paid', false);
        }]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        // Terapkan sorting
        $query->orderBy($sortBy, $sortDir);

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
     * POST /penghuni
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nik' => 'required|string|size:16',
            'foto_ktp' => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'status_penghuni' => 'required|in:tetap,kontrak',
            'telephone' => 'required|string|max:20',
            'is_menikah' => 'required|boolean',
        ]);

        $data = $request->only(['nama', 'nik', 'status_penghuni', 'telephone', 'is_menikah']);

        if ($request->hasFile('foto_ktp')) {
            $image = Image::decode($request->file('foto_ktp'));
            $image->scale(width: 800);

            $filename = 'ktp/'.uniqid().'.jpg';
            Storage::disk('public')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));
            $data['foto_ktp'] = $filename;
        }

        $penghuni = Penghuni::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Penghuni berhasil ditambahkan',
            'data' => $penghuni,
        ], 201);
    }

    /**
     * PATCH /penghuni/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $penghuni = Penghuni::find($id);

        if (! $penghuni) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'nama' => 'sometimes|string|max:255',
            'nik' => 'sometimes|string|size:16',
            'foto_ktp' => 'sometimes|file|mimes:jpg,jpeg,png|max:2048',
            'status_penghuni' => 'sometimes|in:tetap,kontrak',
            'telephone' => 'sometimes|string|max:20',
            'is_menikah' => 'sometimes|boolean',
        ]);

        $data = $request->only(['nama', 'nik', 'status_penghuni', 'telephone', 'is_menikah']);

        if ($request->hasFile('foto_ktp')) {
            // Hapus foto lama jika ada
            if ($penghuni->foto_ktp) {
                Storage::disk('public')->delete($penghuni->foto_ktp);
            }

            $image = Image::decode($request->file('foto_ktp'));
            $image->scale(width: 800);

            $filename = 'ktp/'.uniqid().'.jpg';
            Storage::disk('public')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));
            $data['foto_ktp'] = $filename;
        }

        $penghuni->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Penghuni berhasil diperbarui',
            'data' => $penghuni->fresh(),
        ]);
    }

    /**
     * DELETE /penghuni/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $penghuni = Penghuni::find($id);

        if (! $penghuni) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        // Cek apakah masih ada tagihan aktif (belum dibayar)
        $hasTagihanAktif = $penghuni->tagihan()->where('is_paid', false)->exists();
        if ($hasTagihanAktif) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak dapat menghapus penghuni yang masih memiliki tagihan aktif',
            ], 422);
        }

        // Hapus foto KTP jika ada
        if ($penghuni->foto_ktp) {
            Storage::disk('public')->delete($penghuni->foto_ktp);
        }

        $penghuni->delete();

        return response()->json([
            'status' => true,
            'message' => 'Penghuni berhasil dihapus',
        ]);
    }

    /**
     * GET /penghuni/{id}/riwayat-pembayaran/pagination
     */
    public function riwayatPembayaran(Request $request, string $id): JsonResponse
    {
        $penghuni = Penghuni::find($id);

        if (! $penghuni) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 15));

        $query = $penghuni->pembayaran()->with(['tagihan.iuran', 'tagihan.rumah'])
            ->orderByDesc('created_at');

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
     * GET /penghuni/{id}/tagihan/pagination
     */
    public function tagihan(Request $request, string $id): JsonResponse
    {
        $penghuni = Penghuni::find($id);

        if (! $penghuni) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 15));

        $query = $penghuni->tagihan()->with(['iuran', 'rumah', 'pembayaran'])
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
