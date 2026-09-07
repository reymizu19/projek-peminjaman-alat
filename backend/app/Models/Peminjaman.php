<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Peminjaman extends Model
{
    protected $table = 'peminjaman';
    protected $fillable = ['user_id', 'tanggal_pinjam', 'tgl_pinjam', 'tanggal_kembali_plan', 'tgl_kembali_plan', 'status'];

    protected function casts(): array
    {
        return [
            'tanggal_pinjam' => 'datetime:Y-m-d H:i:s',
            'tanggal_kembali_plan' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function setTglPinjamAttribute($value): void
    {
        $this->attributes['tanggal_pinjam'] = $value;
    }

    public function setTanggalPinjamAttribute($value): void
    {
        $this->attributes['tanggal_pinjam'] = $value;
    }

    public function getTglPinjamAttribute()
    {
        return $this->attributes['tanggal_pinjam'] ?? null;
    }

    public function getTanggalPinjamAttribute()
    {
        return $this->attributes['tanggal_pinjam'] ?? null;
    }

    public function setTglKembaliPlanAttribute($value): void
    {
        $this->attributes['tanggal_kembali_plan'] = $value;
    }

    public function setTanggalKembaliPlanAttribute($value): void
    {
        $this->attributes['tanggal_kembali_plan'] = $value;
    }

    public function getTglKembaliPlanAttribute()
    {
        return $this->attributes['tanggal_kembali_plan'] ?? null;
    }

    public function getTanggalKembaliPlanAttribute()
    {
        return $this->attributes['tanggal_kembali_plan'] ?? null;
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function detailPinjams(): HasMany {
        return $this->hasMany(DetailPinjam::class);
    }

    // Alias agar kode lama tetap bekerja sambil nama relasi baru dipakai di petugas
    public function detailPinjam(): HasMany {
        return $this->detailPinjams();
    }

    public function pengembalian(): HasOne {
        return $this->hasOne(Pengembalian::class);
    }
}
