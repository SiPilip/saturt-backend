<?php

namespace App\Http\Controllers;

use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;

class KeuanganExportController extends Controller
{
    /**
     * GET /keuangan/export?bulan=&tahun=
     *
     * Export buku kas dalam format Debit-Kredit (single sheet).
     */
    public function export(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $filename = "Buku-Kas-RT-{$namaBulan[$bulan]}-{$tahun}.xlsx";

        return Excel::download(
            new BukuKasExport($bulan, $tahun),
            $filename
        );
    }
}

/**
 * Buku Kas RT — Single Sheet, format Debit/Kredit dengan saldo berjalan.
 *
 * Kolom: No | Tanggal | Keterangan | Kategori | Debit (Masuk) | Kredit (Keluar) | Saldo
 */
class BukuKasExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly int $bulan,
        private readonly int $tahun
    ) {}

    public function title(): string
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return "Buku Kas {$namaBulan[$this->bulan]} {$this->tahun}";
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Keterangan', 'Kategori', 'Debit (Masuk)', 'Kredit (Keluar)', 'Saldo'];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        // Ambil semua pemasukan bulan ini
        $pemasukan = Pemasukan::whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($item) => [
                'tanggal' => $item->tanggal,
                'keterangan' => $item->nama,
                'kategori' => ucfirst(str_replace('_', ' ', $item->jenis)),
                'debit' => $item->biaya,
                'kredit' => 0,
            ]);

        // Ambil semua pengeluaran bulan ini
        $pengeluaran = Pengeluaran::whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($item) => [
                'tanggal' => $item->tanggal,
                'keterangan' => $item->nama,
                'kategori' => ucfirst($item->jenis),
                'debit' => 0,
                'kredit' => $item->biaya,
            ]);

        // Gabungkan dan urutkan berdasarkan tanggal
        $transaksi = $pemasukan->concat($pengeluaran)
            ->sortBy('tanggal')
            ->values();

        // Hitung saldo awal dari semua transaksi SEBELUM bulan ini
        $awalBulan = \Carbon\Carbon::create($this->tahun, $this->bulan, 1)->startOfDay();

        $totalPemasukanSebelumnya = Pemasukan::where('tanggal', '<', $awalBulan)->sum('biaya');
        $totalPengeluaranSebelumnya = Pengeluaran::where('tanggal', '<', $awalBulan)->sum('biaya');
        $saldoAwal = $totalPemasukanSebelumnya - $totalPengeluaranSebelumnya;

        // Bangun array baris dengan saldo berjalan
        $rows = [];
        $saldo = $saldoAwal;
        $no = 1;

        // Baris pertama: Saldo Awal
        $rows[] = [
            '',
            '01/' . str_pad((string) $this->bulan, 2, '0', STR_PAD_LEFT) . '/' . $this->tahun,
            'SALDO AWAL (Bulan Sebelumnya)',
            '',
            '',
            '',
            $saldoAwal,
        ];

        foreach ($transaksi as $item) {
            $saldo += $item['debit'] - $item['kredit'];

            $rows[] = [
                $no++,
                $item['tanggal']->format('d/m/Y'),
                $item['keterangan'],
                $item['kategori'],
                $item['debit'] > 0 ? $item['debit'] : '',
                $item['kredit'] > 0 ? $item['kredit'] : '',
                $saldo,
            ];
        }

        // Baris total di paling bawah
        $totalDebit = $transaksi->sum('debit');
        $totalKredit = $transaksi->sum('kredit');

        $rows[] = []; // baris kosong sebagai pemisah
        $rows[] = [
            '',
            '',
            'TOTAL BULAN INI',
            '',
            $totalDebit,
            $totalKredit,
            '',
        ];
        $rows[] = [
            '',
            '',
            'SALDO AKHIR',
            '',
            '',
            '',
            $saldo,
        ];

        return $rows;
    }
}
