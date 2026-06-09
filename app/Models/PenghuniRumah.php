<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenghuniRumah extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'penghuni_rumah';

    protected $fillable = [
        'id_penghuni',
        'id_rumah',
        'tanggal_masuk',
        'tanggal_keluar',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
        ];
    }

    public function penghuni(): BelongsTo
    {
        return $this->belongsTo(Penghuni::class, 'id_penghuni');
    }

    public function rumah(): BelongsTo
    {
        return $this->belongsTo(Rumah::class, 'id_rumah');
    }
}
