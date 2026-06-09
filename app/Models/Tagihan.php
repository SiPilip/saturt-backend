<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tagihan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tagihan';

    protected $fillable = [
        'id_rumah',
        'id_iuran',
        'id_penghuni',
        'bulan',
        'tahun',
        'nominal',
        'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'bulan' => 'integer',
            'tahun' => 'integer',
        ];
    }

    public function rumah(): BelongsTo
    {
        return $this->belongsTo(Rumah::class, 'id_rumah');
    }

    public function iuran(): BelongsTo
    {
        return $this->belongsTo(Iuran::class, 'id_iuran');
    }

    public function penghuni(): BelongsTo
    {
        return $this->belongsTo(Penghuni::class, 'id_penghuni');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'id_tagihan');
    }
}
