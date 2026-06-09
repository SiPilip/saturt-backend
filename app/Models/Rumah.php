<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rumah extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'rumah';

    protected $fillable = [
        'blok_nomor',
        'is_filled',
    ];

    protected function casts(): array
    {
        return [
            'is_filled' => 'boolean',
        ];
    }

    public function penghuniRumah(): HasMany
    {
        return $this->hasMany(PenghuniRumah::class, 'id_rumah');
    }

    public function penghuniAktif(): HasMany
    {
        return $this->hasMany(PenghuniRumah::class, 'id_rumah')
            ->whereNull('tanggal_keluar');
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'id_rumah');
    }
}
