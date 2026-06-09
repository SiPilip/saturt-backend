<?php

namespace Database\Seeders;

use App\Models\Iuran;
use Illuminate\Database\Seeder;

class IuranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $iuran = [
            ['nama' => 'Satpam', 'biaya' => 100000],
            ['nama' => 'Kebersihan', 'biaya' => 15000],
        ];

        foreach ($iuran as $item) {
            Iuran::create($item);
        }
    }
}
