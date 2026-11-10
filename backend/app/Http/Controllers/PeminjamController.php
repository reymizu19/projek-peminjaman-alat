<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DetailPinjam;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamController extends Controller
{
    // Melihat daftar/katalog alat yang tersedia
    public function katalogAlat(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $kategori = $request->query('kategori');

        $alats = Alat::with('kategori')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama_alat', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%");
                });
            })
            ->when($kategori, fn ($query, $kategori) => $query->where('kategori_id', $kategori))
            ->orderBy('nama_alat')
            ->get();

        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('peminjam.katalog', compact('alats', 'kategoris', 'search', 'kategori'));
    }

    public function ajukanPeminjaman(Request $request)
    {
        $request->validate([
            'tgl_kembali_plan' => 'required|date|after:today',
            'alat_id' => 'required|array|min:1',
            'alat_id.*' => 'integer|exists:alat,id',
            'jumlah' => 'required|array',
            'alasan' => 'required|string|max:500',
        ], [
            'alat_id.required' => 'Pilih minimal satu alat yang ingin dipinjam.',
            'alat_id.array' => 'Pilihan alat tidak valid.',
            'alat_id.min' => 'Pilih minimal satu alat yang ingin dipinjam.',
        ]);

        DB::beginTransaction();

        try {
            $tanggalKembaliPlan = now()->setDate(
                (int) date('Y', strtotime($request->tgl_kembali_plan)),
                (int) date('m', strtotime($request->tgl_kembali_plan)),
                (int) date('d', strtotime($request->tgl_kembali_plan))
            );

            // Buat header peminjaman
            $peminjaman = Peminjaman::create([
                'user_id' => auth()->id(),
                'tanggal_pinjam' => now(),
                'tanggal_kembali_plan' => $tanggalKembaliPlan,
                'alasan' => $request->alasan,
                'status' => 'diajukan',
                'stok_dicadangkan' => true,
            ]);

            // Masukkan daftar alat yang dipinjam ke detail_pinjam
            foreach ($request->alat_id as $alatId) {
                $jumlah = (int) ($request->jumlah[$alatId] ?? 0);
                $alat = Alat::lockForUpdate()->findOrFail($alatId);

                if ($jumlah < 1 || $jumlah > $alat->stok) {
                    throw new \RuntimeException("Jumlah alat {$alat->nama_alat} tidak valid.");
                }

                $alat->decrement('stok', $jumlah);

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alatId,
                    'jumlah' => $jumlah,
                ]);
            }

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => "Peminjam " . auth()->user()->name . " mengajukan peminjaman #{$peminjaman->id}.",
            ]);

            DB::commit();

            return redirect()->route('peminjam.peminjaman.saya')
                ->with('success', 'Pengajuan peminjaman berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Gagal mengajukan peminjaman: '.$e->getMessage());
        }
    }

    public function peminjamanSaya()
    {
        $peminjamans = Peminjaman::with('detailPinjams.alat')
            ->where('user_id', auth()->id())
            ->whereIn('status', ['diajukan', 'dipinjam', 'menunggu_pengembalian'])
            ->latest()
            ->get();

        return view('peminjam.peminjaman-saya', compact('peminjamans'));
    }

    public function ajukanPengembalian(Peminjaman $peminjaman)
    {
        abort_unless($peminjaman->user_id === auth()->id(), 403);

        if ($peminjaman->status !== 'dipinjam') {
            return redirect()->back()->with('error', 'Peminjaman ini tidak dapat diajukan untuk dikembalikan.');
        }

        DB::transaction(function () use ($peminjaman) {
            $peminjaman->update([
                'status' => 'menunggu_pengembalian',
                'pengembalian_diajukan_at' => now(),
            ]);

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => "Peminjam " . auth()->user()->name . " mengajukan pengembalian untuk peminjaman #{$peminjaman->id}.",
            ]);
        });

        return redirect()->route('peminjam.peminjaman.saya')
            ->with('success', 'Permintaan pengembalian berhasil dikirim. Menunggu pemeriksaan petugas.');
    }

    // Melihat riwayat peminjaman user yang sudah selesai
    public function riwayatPeminjaman()
    {
        $peminjamans = Peminjaman::with(['detailPinjams.alat', 'pengembalian'])
            ->where('user_id', auth()->id())
            ->whereIn('status', ['dikembalikan', 'telat'])
            ->latest()
            ->get();

        return view('peminjam.riwayat', compact('peminjamans'));
    }

    public function editPeminjaman(Peminjaman $peminjaman)
    {
        $this->ensureEditable($peminjaman);

        $selected = $peminjaman->detailPinjams->keyBy('alat_id');
        $alats = Alat::with('kategori')
            ->where(function ($query) use ($selected) {
                $query->where('stok', '>', 0)
                    ->orWhereIn('id', $selected->keys());
            })
            ->orderBy('nama_alat')
            ->get();
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('peminjam.peminjaman.edit', compact('peminjaman', 'alats', 'kategoris', 'selected'));
    }

    public function updatePeminjaman(Request $request, Peminjaman $peminjaman)
    {
        $this->ensureEditable($peminjaman);

        $data = $request->validate([
            'tgl_kembali_plan' => 'nullable|date|after:today',
            'alat_id' => 'nullable|array|min:1',
            'alat_id.*' => 'integer|exists:alat,id',
            'jumlah' => 'nullable|array',
            'jumlah.*' => 'integer|min:1',
            'alasan' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $peminjaman) {
            $updates = [];

            if ($request->filled('tgl_kembali_plan')) {
                $updates['tanggal_kembali_plan'] = $request->tgl_kembali_plan;
            }

            if ($request->filled('alasan')) {
                $updates['alasan'] = $request->alasan;
            }

            if ($updates) {
                $peminjaman->update($updates);
            }

            if ($request->filled('alat_id')) {
                $details = $peminjaman->detailPinjams()->get();
                $reserved = ($peminjaman->stok_dicadangkan ? $details : collect())->groupBy('alat_id')
                    ->map(fn ($items) => $items->sum('jumlah'));
                $requested = [];

                foreach ($request->alat_id as $alatId) {
                    $requested[$alatId] = ($requested[$alatId] ?? 0) + (int) ($request->jumlah[$alatId] ?? 0);
                }

                $alatIds = $reserved->keys()->merge(array_keys($requested))->unique()->sort();
                $alats = Alat::whereIn('id', $alatIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                foreach ($requested as $alatId => $jumlah) {
                    $alat = $alats->get($alatId);

                    if (! $alat || $jumlah < 1 || $jumlah > $alat->stok + ($reserved[$alatId] ?? 0)) {
                        throw new \RuntimeException('Jumlah alat tidak valid atau stok tidak mencukupi.');
                    }
                }

                foreach ($alatIds as $alatId) {
                    $selisih = ($requested[$alatId] ?? 0) - ($reserved[$alatId] ?? 0);
                    $alat = $alats->get($alatId);

                    if ($selisih > 0) {
                        $alat->decrement('stok', $selisih);
                    } elseif ($selisih < 0) {
                        $alat->increment('stok', abs($selisih));
                    }
                }

                $peminjaman->detailPinjams()->delete();
                $peminjaman->update(['stok_dicadangkan' => true]);

                foreach ($request->alat_id as $alatId) {
                    $jumlah = (int) ($request->jumlah[$alatId] ?? 0);

                    DetailPinjam::create([
                        'peminjaman_id' => $peminjaman->id,
                        'alat_id' => $alatId,
                        'jumlah' => $jumlah,
                    ]);
                }
            }
        });

        return redirect()->route('peminjam.peminjaman.saya')->with('success', 'Pengajuan peminjaman berhasil diperbarui.');
    }

    public function destroyPeminjaman(Peminjaman $peminjaman)
    {
        $this->ensureEditable($peminjaman);

        DB::transaction(function () use ($peminjaman) {
            foreach ($peminjaman->detailPinjams as $detail) {
                if ($peminjaman->stok_dicadangkan) {
                    Alat::lockForUpdate()->findOrFail($detail->alat_id)->increment('stok', $detail->jumlah);
                }
            }

            $peminjaman->detailPinjams()->delete();
            $peminjaman->delete();
        });

        return redirect()->route('peminjam.peminjaman.saya')->with('success', 'Pengajuan peminjaman berhasil dihapus.');
    }

    private function ensureEditable(Peminjaman $peminjaman): void
    {
        abort_unless($peminjaman->user_id === auth()->id(), 403);
        abort_unless($peminjaman->status === 'diajukan', 403);
    }
}
