<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PetugasController extends Controller
{
    // Menampilkan daftar pengajuan peminjaman dari siswa/peminjam
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');
        $peminjamans = Peminjaman::with(['user', 'detailPinjams.alat'])
            ->where('status', 'diajukan')
            ->when($search, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return view('petugas.peminjaman.index', compact('peminjamans', 'search'));
    }

    // Menyetujui Peminjaman (Mengubah status & mengurangi stok alat)
    public function setujuiPeminjaman($id)
    {
        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::with('detailPinjams')->findOrFail($id);
            $peminjaman->update(['status' => 'dipinjam']);

            foreach ($peminjaman->detailPinjams as $detail) {
                $alat = Alat::findOrFail($detail->alat_id);
                $alat->stok -= $detail->jumlah;
                $alat->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Peminjaman disetujui dan stok alat dikurangi.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Menolak Peminjaman (Menghapus pengajuan agar siswa bisa mengajukan ulang)
    public function tolakPeminjaman($id)
    {
        try {
            $peminjaman = Peminjaman::findOrFail($id);
            if ($peminjaman->status == 'diajukan') {
                $peminjaman->delete();
                return redirect()->back()->with('success', 'Pengajuan peminjaman berhasil ditolak.');
            }
            return redirect()->back()->with('error', 'Status peminjaman sudah berubah.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');

        $peminjamans = Peminjaman::with(['user', 'detailPinjams.alat'])
            ->where('status', 'dipinjam')
            ->when($search, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return view('petugas.pengembalian.index', compact('peminjamans', 'search'));
    }

    public function terimaPengembalian(Request $request, $id)
    {
        $request->validate([
            'kondisi_kembali' => 'required|string|max:255',
            'denda' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $peminjaman = Peminjaman::with('detailPinjams')->lockForUpdate()->findOrFail($id);

            if ($peminjaman->status !== 'dipinjam') {
                throw new Exception("Data ditolak. Peminjaman ini berstatus '{$peminjaman->status}', bukan 'dipinjam'.");
            }

            $tglKembaliPlan = \Carbon\Carbon::parse($peminjaman->tanggal_kembali_plan ?? $peminjaman->tgl_kembali_plan)->startOfDay();
            $hariIni = \Carbon\Carbon::now()->startOfDay();
            $statusPeminjamanBaru = $hariIni->greaterThan($tglKembaliPlan) ? 'telat' : 'dikembalikan';

            Pengembalian::create([
                'peminjaman_id' => $peminjaman->id,
                'tanggal_kembali' => now(),
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda' => $request->denda ?? 0,
                'petugas_id' => auth()->id(),
            ]);

            $peminjaman->update(['status' => $statusPeminjamanBaru]);

            foreach ($peminjaman->detailPinjams as $detail) {
                $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                $alat->increment('stok', $detail->jumlah);
            }

            DB::commit();

            return redirect()->route('petugas.pengembalian.index')->with('success', 'Pengembalian berhasil diproses.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function buildLaporanFilters(Request $request): array
    {
        $search = trim((string) $request->input('search', ''));
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $errors = [];

        if ($tanggalMulai && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalMulai)) {
            $errors[] = 'Format tanggal mulai tidak valid.';
        }

        if ($tanggalSelesai && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSelesai)) {
            $errors[] = 'Format tanggal selesai tidak valid.';
        }

        if ($tanggalMulai && $tanggalSelesai && $tanggalMulai > $tanggalSelesai) {
            $errors[] = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.';
        }

        return [
            'search' => $search,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'errors' => $errors,
        ];
    }

    private function getLaporanPeminjamanQuery($search = null, $tanggalMulai = null, $tanggalSelesai = null)
    {
        return Peminjaman::with(['user', 'detailPinjams.alat', 'pengembalian'])
            ->when($search, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhere('status', 'like', "%{$search}%");
            })
            ->when($tanggalMulai && ! $tanggalSelesai, function ($query) use ($tanggalMulai) {
                return $query->whereDate('tanggal_pinjam', '>=', $tanggalMulai);
            })
            ->when($tanggalSelesai && ! $tanggalMulai, function ($query) use ($tanggalSelesai) {
                return $query->whereDate('tanggal_pinjam', '<=', $tanggalSelesai);
            })
            ->when($tanggalMulai && $tanggalSelesai, function ($query) use ($tanggalMulai, $tanggalSelesai) {
                return $query->whereBetween(DB::raw('DATE(tanggal_pinjam)'), [$tanggalMulai, $tanggalSelesai]);
            })
            ->latest();
    }

    public function indexLaporan(Request $request)
    {
        $filters = $this->buildLaporanFilters($request);

        if (! empty($filters['errors'])) {
            return redirect()->route('petugas.laporan.index', [
                'search' => $filters['search'],
                'tanggal_mulai' => $filters['tanggal_mulai'],
                'tanggal_selesai' => $filters['tanggal_selesai'],
            ])->withErrors(['tanggal_mulai' => $filters['errors'][0]]);
        }

        $peminjamans = $this->getLaporanPeminjamanQuery(
            $filters['search'],
            $filters['tanggal_mulai'],
            $filters['tanggal_selesai']
        )->get();

        return view('petugas.laporan.index', [
            'peminjamans' => $peminjamans,
            'search' => $filters['search'],
            'tanggal_mulai' => $filters['tanggal_mulai'],
            'tanggal_selesai' => $filters['tanggal_selesai'],
        ]);
    }

    public function pdfLaporan(Request $request)
    {
        $filters = $this->buildLaporanFilters($request);

        if (! empty($filters['errors'])) {
            return redirect()->route('petugas.laporan.index', [
                'search' => $filters['search'],
                'tanggal_mulai' => $filters['tanggal_mulai'],
                'tanggal_selesai' => $filters['tanggal_selesai'],
            ])->withErrors(['tanggal_mulai' => $filters['errors'][0]]);
        }

        $peminjamans = $this->getLaporanPeminjamanQuery(
            $filters['search'],
            $filters['tanggal_mulai'],
            $filters['tanggal_selesai']
        )->get();

        $periodeText = 'Semua data';
        if ($filters['search']) {
            $periodeText = 'Pencarian: ' . $filters['search'];
        }
        if ($filters['tanggal_mulai'] || $filters['tanggal_selesai']) {
            $periodeText = 'Periode: ' . ($filters['tanggal_mulai'] ?? '-') . ' s/d ' . ($filters['tanggal_selesai'] ?? '-');
        }
        if ($filters['search'] && ($filters['tanggal_mulai'] || $filters['tanggal_selesai'])) {
            $periodeText = 'Pencarian: ' . $filters['search'] . ' | Periode: ' . ($filters['tanggal_mulai'] ?? '-') . ' s/d ' . ($filters['tanggal_selesai'] ?? '-');
        }

        $pdf = Pdf::loadView('petugas.laporan.pdf', [
            'peminjamans' => $peminjamans,
            'search' => $filters['search'],
            'printedAt' => now()->translatedFormat('d F Y'),
            'printedAtDateTime' => now()->format('d-m-Y H:i:s'),
            'periode' => $periodeText,
            'tanggal_mulai' => $filters['tanggal_mulai'],
            'tanggal_selesai' => $filters['tanggal_selesai'],
        ]);

        return $pdf->setPaper('A4', 'landscape')->stream('laporan-peminjaman-' . now()->format('YmdHis') . '.pdf');
    }
}
