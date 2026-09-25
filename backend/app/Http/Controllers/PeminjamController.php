<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use App\Models\Kategori;
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
            ]);

            // Masukkan daftar alat yang dipinjam ke detail_pinjam
            foreach ($request->alat_id as $alatId) {
                $jumlah = (int) ($request->jumlah[$alatId] ?? 0);
                $alat = Alat::findOrFail($alatId);

                if ($jumlah < 1 || $jumlah > $alat->stok) {
                    throw new \RuntimeException("Jumlah alat {$alat->nama_alat} tidak valid.");
                }

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alatId,
                    'jumlah' => $jumlah,
                ]);
            }

            DB::commit();

            return redirect()->route('peminjam.riwayat')
                ->with('success', 'Pengajuan peminjaman berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Gagal mengajukan peminjaman: ' . $e->getMessage());
        }
    }

    // Melihat riwayat peminjaman user yang sedang login
    public function riwayatPeminjaman()
    {
        $peminjamans = Peminjaman::with(['detailPinjams.alat', 'pengembalian'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('peminjam.riwayat', compact('peminjamans'));
    }

    public function editPeminjaman(Peminjaman $peminjaman)
    {
        $this->ensureEditable($peminjaman);

        $alats = Alat::with('kategori')->where('stok', '>', 0)->orderBy('nama_alat')->get();
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        $selected = $peminjaman->detailPinjams->keyBy('alat_id');

        return view('peminjam.peminjaman.edit', compact('peminjaman', 'alats', 'kategoris', 'selected'));
    }

    public function updatePeminjaman(Request $request, Peminjaman $peminjaman)
    {
        $this->ensureEditable($peminjaman);

        $data = $request->validate([
            'tgl_kembali_plan' => 'required|date|after:today',
            'alat_id' => 'required|array|min:1',
            'alat_id.*' => 'integer|exists:alat,id',
            'jumlah' => 'required|array',
            'alasan' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($request, $peminjaman) {
            $tanggalKembaliPlan = now()->setDate(
                (int) date('Y', strtotime($request->tgl_kembali_plan)),
                (int) date('m', strtotime($request->tgl_kembali_plan)),
                (int) date('d', strtotime($request->tgl_kembali_plan))
            );

            $peminjaman->update([
                'tanggal_kembali_plan' => $tanggalKembaliPlan,
                'alasan' => $request->alasan,
            ]);
            $peminjaman->detailPinjams()->delete();

            foreach ($request->alat_id as $alatId) {
                $jumlah = (int) ($request->jumlah[$alatId] ?? 0);
                $alat = Alat::findOrFail($alatId);

                if ($jumlah < 1 || $jumlah > $alat->stok) {
                    throw new \RuntimeException("Jumlah alat {$alat->nama_alat} tidak valid.");
                }

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alatId,
                    'jumlah' => $jumlah,
                ]);
            }
        });

        return redirect()->route('peminjam.riwayat')->with('success', 'Pengajuan peminjaman berhasil diperbarui.');
    }

    public function destroyPeminjaman(Peminjaman $peminjaman)
    {
        $this->ensureEditable($peminjaman);
        $peminjaman->detailPinjams()->delete();
        $peminjaman->delete();

        return redirect()->route('peminjam.riwayat')->with('success', 'Pengajuan peminjaman berhasil dihapus.');
    }

    private function ensureEditable(Peminjaman $peminjaman): void
    {
        abort_unless($peminjaman->user_id === auth()->id(), 403);
        abort_unless($peminjaman->status === 'diajukan', 403);
    }

}