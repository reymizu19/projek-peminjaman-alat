<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengembalian extends Model
{
    protected $table = 'pengembalian';
    protected $fillable = ['peminjaman_id', 'tanggal_kembali', 'tgl_kembali', 'kondisi_kembali', 'denda', 'petugas_id'];

    protected function casts(): array
    {
        return [
            'tanggal_kembali' => 'datetime:Y-m-d H:i:s',
            'denda' => 'integer',
        ];
    }

    public function setTglKembaliAttribute($value): void
    {
        $this->attributes['tanggal_kembali'] = $value;
    }

    public function setTanggalKembaliAttribute($value): void
    {
        $this->attributes['tanggal_kembali'] = $value;
    }

    public function getTglKembaliAttribute()
    {
        return $this->attributes['tanggal_kembali'] ?? null;
    }

    public function getTanggalKembaliAttribute()
    {
        return $this->attributes['tanggal_kembali'] ?? null;
    }

    public function peminjaman(): BelongsTo {
        return $this->belongsTo(Peminjaman::class);
    }

    public function petugas(): BelongsTo {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
