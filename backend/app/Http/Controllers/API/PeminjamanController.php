<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Peminjaman\StorePeminjamanRequest;
use App\Http\Resources\PeminjamanResource;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use App\Models\LogAktivitas;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use Exception;

class PeminjamanController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $query = Peminjaman::with(['user', 'detailPinjam.alat', 'pengembalian']);
        if ($user->role === 'peminjam') {
            $query->where('user_id', $user->id);
        }
        $peminjaman = $query->latest()->get();
        return response()->json([
            'message' => 'Daftar peminjaman berhasil diambil.',
            'data' => PeminjamanResource::collection($peminjaman)
        ]);
    }


    public function store(StorePeminjamanRequest $request): JsonResponse
    {
        try {
        $peminjaman = DB::transaction(function () use ($request) {
            $user = auth()->user();
            $tanggalKembaliPlan = now()->setDate(
                (int) date('Y', strtotime($request->tgl_kembali_plan)),
                (int) date('m', strtotime($request->tgl_kembali_plan)),
                (int) date('d', strtotime($request->tgl_kembali_plan))
            );

            $peminjaman = Peminjaman::create([
                'user_id' => $user->id,
                'tanggal_pinjam' => now(),
                'tanggal_kembali_plan' => $tanggalKembaliPlan,
                'status' => 'diajukan',
                'stok_dicadangkan' => true,
            ]);

            foreach ($request->items as $item) {
            // lockForUpdate mengunci baris data di database sampai transaksi ini COMMIT
            $alat = Alat::lockForUpdate()->findOrFail($item['alat_id']);
                if ($alat->stok < $item['jumlah']) {
                    throw new Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi. Sisa stok: {$alat->stok}");
                }
                $alat->decrement('stok', $item['jumlah']);

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $item['alat_id'],
                    'jumlah' => $item['jumlah'],
                ]);
            }

            LogAktivitas::create([
                'user_id' => $user->id,
                'aktivitas' => "Peminjam {$user->name} mengajukan peminjaman #{$peminjaman->id} melalui API.",
            ]);

            return $peminjaman->load(['user', 'detailPinjam.alat']);
        });
        return response()->json([
        'message' => 'Peminjaman berhasil diajukan. Menunggu persetujuan petugas.', 'data' => new PeminjamanResource($peminjaman)], 201); //Dioptimalkan menggunakan Resource
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }


    public function show(Peminjaman $peminjaman): JsonResponse
    {
        $user = auth()->user();

        if ($user->role === 'peminjam' && $peminjaman->user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        return response()->json([
            'message' => 'Detail peminjaman berhasil diambil.',
            'data' => new PeminjamanResource($peminjaman->load(['user', 'detailPinjam.alat', 'pengembalian']))
        ]);
    }


    public function update(StorePeminjamanRequest $request, Peminjaman
    $peminjaman): JsonResponse
    {
        $user = auth()->user();
        if ($user->role === 'peminjam' && $peminjaman->user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        if ($peminjaman->status !== 'diajukan') {
            return response()->json(['message' => "Peminjaman tidak dapat diubah karena status saat ini: {$peminjaman->status}."], 400);
        }
        try {
            DB::transaction(function () use ($request, $peminjaman) {
                $peminjaman->update(['tgl_kembali_plan' => $request->tgl_kembali_plan,]);

                $reserved = ($peminjaman->stok_dicadangkan ? $peminjaman->detailPinjam()->get() : collect())
                    ->groupBy('alat_id')
                    ->map(fn ($items) => $items->sum('jumlah'));
                $requested = [];

                foreach ($request->items as $item) {
                    $alatId = (int) $item['alat_id'];
                    $requested[$alatId] = ($requested[$alatId] ?? 0) + (int) $item['jumlah'];
                }

                $alatIds = $reserved->keys()->merge(array_keys($requested))->unique()->sort();
                $alats = Alat::whereIn('id', $alatIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                foreach ($requested as $alatId => $jumlah) {
                    $alat = $alats->get($alatId);

                    if (! $alat || $alat->stok + ($reserved[$alatId] ?? 0) < $jumlah) {
                        throw new Exception("Stok alat tidak mencukupi untuk diperbarui.");
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

                $peminjaman->detailPinjam()->delete();
                $peminjaman->update(['stok_dicadangkan' => true]);

                foreach ($request->items as $item) {
                    DetailPinjam::create([
                        'peminjaman_id' => $peminjaman->id,
                        'alat_id' => $item['alat_id'],
                        'jumlah' => $item['jumlah'],
                    ]);
                }
            });
            return response()->json([
                'message' => 'Data permohonan peminjaman berhasil diperbarui.',
                'data' => new PeminjamanResource($peminjaman->load(['user', 'detailPinjam.alat']))
            ]); 
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }


    public function destroy(Peminjaman $peminjaman): JsonResponse
    {
        $user = auth()->user();

        if ($user->role === 'peminjam' && $peminjaman->user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($peminjaman->status !== 'diajukan') {
            return response()->json(['message' => 'Peminjaman tidak dapat dibatalkan.'], 400);
        }

        DB::transaction(function () use ($peminjaman) {
            if ($peminjaman->stok_dicadangkan) {
                foreach ($peminjaman->detailPinjam as $detail) {
                    Alat::lockForUpdate()->findOrFail($detail->alat_id)->increment('stok', $detail->jumlah);
                }
            }

            $peminjaman->detailPinjam()->delete(); // Hapus child record terlebih dahulu
            $peminjaman->delete();
        });
        
        return response()->json([
            'message' => 'Permohonan peminjaman berhasil dibatalkan dan dihapus.'
        ]);
    }

    public function approve(Peminjaman $peminjaman): JsonResponse
    {
        try {
            $peminjaman = DB::transaction(function () use ($peminjaman) {
                $peminjaman = Peminjaman::lockForUpdate()->findOrFail($peminjaman->id);

                if ($peminjaman->status !== 'diajukan') {
                    throw new Exception("Persetujuan gagal. Status saat ini: {$peminjaman->status}.");
                }

                if (! $peminjaman->stok_dicadangkan) {
                    foreach ($peminjaman->detailPinjam as $detail) {
                        $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);

                        if ($alat->stok < $detail->jumlah) {
                            throw new Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi.");
                        }

                        $alat->decrement('stok', $detail->jumlah);
                    }
                }

                $peminjaman->update([
                    'status' => 'dipinjam',
                    'stok_dicadangkan' => true,
                ]);

                LogAktivitas::create([
                    'user_id' => auth()->id(),
                    'aktivitas' => "Petugas " . auth()->user()->name . " menyetujui peminjaman #{$peminjaman->id} milik {$peminjaman->user->name} melalui API.",
                ]);

                return $peminjaman;
            });
            return response()->json([
                'message' => 'Peminjaman disetujui.',
                'data' => new PeminjamanResource($peminjaman->load(['user', 'detailPinjam.alat']))
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function riwayat(): JsonResponse
    {
        $riwayat = Peminjaman::with(['detailPinjam.alat', 'pengembalian'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
        return response()->json([
            'message' => 'Riwayat peminjaman Anda.',
            'data' => PeminjamanResource::collection($riwayat)
        ]);
    }
}