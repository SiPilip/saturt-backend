<?php

namespace App\Http\Controllers;

use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;

class KeuanganExportController extends Controller
{
    /**
     * GET /keuangan/export?bulan=&tahun=
     */
    public function export(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $filename = "keuangan-{$tahun}-{$bulan}.xlsx";

        return Excel::download(
            new KeuanganExport($bulan, $tahun),
            $filename
        );
    }
}

/**
 * Export class multi-sheet: Pemasukan, Pengeluaran, Ringkasan
 */
class KeuanganExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $bulan,
        private readonly int $tahun
    ) {}

    public function sheets(): array
    {
        return [
            new PemasukanSheet($this->bulan, $this->tahun),
            new PengeluaranSheet($this->bulan, $this->tahun),
            new RingkasanSheet($this->bulan, $this->tahun),
        ];
    }
}

class PemasukanSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly int $bulan,
        private readonly int $tahun
    ) {}

    public function title(): string
    {
        return 'Pemasukan';
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'Jenis', 'Jumlah (Rp)', 'Tanggal'];
    }

    public function collection()
    {
        return Pemasukan::whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($item, $index) => [
                'no' => $index + 1,
                'nama' => $item->nama,
                'jenis' => $item->jenis,
                'biaya' => $item->biaya,
                'tanggal' => $item->tanggal->format('d/m/Y'),
            ]);
    }
}

class PengeluaranSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly int $bulan,
        private readonly int $tahun
    ) {}

    public function title(): string
    {
        return 'Pengeluaran';
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'Jenis', 'Jumlah (Rp)', 'Tanggal'];
    }

    public function collection()
    {
        return Pengeluaran::whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($item, $index) => [
                'no' => $index + 1,
                'nama' => $item->nama,
                'jenis' => $item->jenis,
                'biaya' => $item->biaya,
                'tanggal' => $item->tanggal->format('d/m/Y'),
            ]);
    }
}

class RingkasanSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly int $bulan,
        private readonly int $tahun
    ) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function headings(): array
    {
        return ['Keterangan', 'Jumlah (Rp)'];
    }

    public function collection()
    {
        $totalPemasukan = Pemasukan::whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->sum('biaya');

        $totalPengeluaran = Pengeluaran::whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->sum('biaya');

        return collect([
            ['Total Pemasukan', $totalPemasukan],
            ['Total Pengeluaran', $totalPengeluaran],
            ['Saldo', $totalPemasukan - $totalPengeluaran],
        ]);
    }
}
