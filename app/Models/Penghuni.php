<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Penghuni extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'penghuni';

    protected $fillable = [
        'nama',
        'nik',
        'foto_ktp',
        'status_penghuni',
        'telephone',
        'is_menikah',
    ];

    protected function casts(): array
    {
        return [
            'is_menikah' => 'boolean',
        ];
    }

    public function penghuniRumah(): HasMany
    {
        return $this->hasMany(PenghuniRumah::class, 'id_penghuni');
    }

    public function rumahAktif(): HasOneThrough
    {
        return $this->hasOneThrough(
            Rumah::class,
            PenghuniRumah::class,
            'id_penghuni',
            'id',
            'id',
            'id_rumah'
        )->whereNull('penghuni_rumah.tanggal_keluar');
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'id_penghuni');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'id_penghuni');
    }
}
