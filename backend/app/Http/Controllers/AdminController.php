<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use App\Models\Pengembalian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    // Menampilkan Dashboard Admin & Log Aktivitas
    public function index()
    {
        $logs = LogAktivitas::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('logs'));
    }

    // CRUD Alat: Menampilkan daftar alat
    public function indexAlat(Request $request)
    {
        $search = $request->input('search');

        $alats = Alat::with('kategori')
            ->when($search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('nama_alat', 'like', "%{$search}%")
                        ->orWhereHas('kategori', function ($kategoriQuery) use ($search) {
                            $kategoriQuery->where('nama_kategori', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.alat.index', compact('alats', 'search'));
    }

    // Menyimpan Alat Baru
    public function storeAlat(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required',
            'nama_alat' => 'required|string|max:255',
            'stok' => 'required|integer',
            'status_kondisi' => 'required|string',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['kategori_id', 'nama_alat', 'stok', 'status_kondisi', 'deskripsi']);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('alat', 'public');
        }

        Alat::create($data);

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menambahkan alat baru: ' . $request->nama_alat,
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Alat berhasil ditambahkan.');
    }

    // Menampilkan form tambah alat
    public function createAlat()
    {
        $kategoris = Kategori::all();

        return view('admin.alat.create', compact('kategoris'));
    }

    // Menampilkan form edit alat
    public function editAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $kategoris = Kategori::all();

        return view('admin.alat.edit', compact('alat', 'kategoris'));
    }

    // Memperbarui data alat
    public function updateAlat(Request $request, $id)
    {
        $request->validate([
            'kategori_id' => 'required',
            'nama_alat' => 'required|string|max:255',
            'stok' => 'required|integer',
            'status_kondisi' => 'required|string',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|max:2048',
        ]);

        $alat = Alat::findOrFail($id);

        $data = $request->only(['kategori_id', 'nama_alat', 'stok', 'status_kondisi', 'deskripsi']);

        if ($request->hasFile('gambar')) {
            if ($alat->gambar && Storage::disk('public')->exists($alat->gambar)) {
                Storage::disk('public')->delete($alat->gambar);
            }

            $data['gambar'] = $request->file('gambar')->store('alat', 'public');
        }

        $alat->update($data);

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Memperbarui alat: ' . $alat->nama_alat,
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Alat berhasil diperbarui.');
    }

    // Menghapus alat
    public function destroyAlat($id)
    {
        $alat = Alat::findOrFail($id);

        $nama = $alat->nama_alat;

        if ($alat->gambar && Storage::disk('public')->exists($alat->gambar)) {
            Storage::disk('public')->delete($alat->gambar);
        }

        $alat->delete();

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus alat: ' . $nama,
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Alat berhasil dihapus.');
    }

    // CRUD User (Manajemen User Admin, Petugas, Peminjam)
    public function indexUser(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.user.index', compact('users', 'search'));
    }

    public function createUser()
    {
        return view('admin.user.create');
    }

    // Menyimpan user baru ke database
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,petugas,peminjam',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'no_hp' => $request->no_hp,
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);

        return view('admin.user.edit', compact('user'));
    }

    // Memperbarui data user
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:admin,petugas,peminjam',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'no_hp' => $request->no_hp,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.user.index')->with('success', 'Data user berhasil diperbarui.');
    }

    // Menghapus user
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return redirect()->route('admin.user.index')->with('success', 'User berhasil dihapus.');
    }

    // CRUD Kategori: Menampilkan daftar kategori
    public function indexKategori(Request $request)
    {
        $search = $request->input('search');

        $kategoris = Kategori::when($search, function ($query, $search) {
                return $query->where('nama_kategori', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('admin.kategori.index', compact('kategoris', 'search'));
    }

    // Menampilkan form tambah kategori
    public function createKategori()
    {
        return view('admin.kategori.create');
    }

    // Menyimpan kategori baru
    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
        ]);

        Kategori::create([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    // Menampilkan form edit kategori
    public function editKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        return view('admin.kategori.edit', compact('kategori'));
    }

    // Memperbarui kategori
    public function updateKategori(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $id,
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    // Menampilkan daftar peminjaman
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');

        $peminjamans = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->when($search, function ($query, $search) {
                return $query->where('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.peminjaman.index', compact('peminjamans', 'search'));
    }

    // Menampilkan daftar pengembalian
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');

        $pengembalians = Pengembalian::with(['peminjaman.user', 'peminjaman.detailPinjam.alat', 'petugas'])
            ->when($search, function ($query, $search) {
                return $query->where('kondisi_kembali', 'like', "%{$search}%")
                    ->orWhereHas('petugas', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('peminjaman.user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengembalian.index', compact('pengembalians', 'search'));
    }

    // Menampilkan form edit pengembalian
    public function editPengembalian($id)
    {
        $pengembalian = Pengembalian::with(['peminjaman.user', 'petugas'])->findOrFail($id);

        return view('admin.pengembalian.edit', compact('pengembalian'));
    }

    // Memperbarui data pengembalian
    public function updatePengembalian(Request $request, $id)
    {
        $request->validate([
            'kondisi_kembali' => 'required|string|max:255',
            'denda' => 'required|integer|min:0',
        ]);

        if (substr_count((string) $request->input('denda'), '0') > 8) {
            return redirect()->back()->withInput()->withErrors(['denda' => 'Biaya denda terlalu besar!']);
        }

        $pengembalian = Pengembalian::findOrFail($id);
        $pengembalian->update([
            'kondisi_kembali' => $request->kondisi_kembali,
            'denda' => $request->denda,
        ]);

        return redirect()->route('admin.pengembalian.index')->with('success', 'Data pengembalian berhasil diperbarui.');
    }

    // Menghapus data pengembalian dan membatalkan pengembalian stok
    public function destroyPengembalian($id)
    {
        DB::beginTransaction();

        try {
            $pengembalian = Pengembalian::findOrFail($id);
            $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($pengembalian->peminjaman_id);

            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::findOrFail($detail->alat_id);

                if ($alat->stok < $detail->jumlah) {
                    throw new \Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi untuk membatalkan pengembalian.");
                }

                $alat->stok -= $detail->jumlah;
                $alat->save();
            }

            $peminjaman->update(['status' => 'dipinjam']);
            $pengembalian->delete();

            DB::commit();

            return redirect()->route('admin.pengembalian.index')->with('success', 'Data pengembalian berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // Menampilkan form tambah peminjaman
    public function createPeminjaman()
    {
        $users = User::where('role', 'peminjam')->get();
        $alats = Alat::where('stok', '>', 0)->get();

        return view('admin.peminjaman.create', compact('users', 'alats'));
    }

    // Menyimpan data peminjaman baru
    public function storePeminjaman(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal_pinjam' => 'required|date',
            'tanggal_kembali_plan' => 'required|date|after_or_equal:tanggal_pinjam',
            'alat_id' => 'required|array|min:1',
            'alat_id.*' => 'exists:alat,id',
            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $tanggalPinjam = now();
            $tanggalKembaliPlan = now()->setDate(
                (int) date('Y', strtotime($request->tanggal_kembali_plan)),
                (int) date('m', strtotime($request->tanggal_kembali_plan)),
                (int) date('d', strtotime($request->tanggal_kembali_plan))
            );

            $peminjaman = Peminjaman::create([
                'user_id' => $request->user_id,
                'tanggal_pinjam' => $tanggalPinjam,
                'tanggal_kembali_plan' => $tanggalKembaliPlan,
                'status' => 'diajukan',
            ]);

            foreach ($request->alat_id as $index => $alatId) {
                $jumlah = (int) ($request->jumlah[$index] ?? 0);

                if ($jumlah < 1) {
                    throw new \Exception('Jumlah alat harus minimal 1.');
                }
                $alat = Alat::findOrFail($alatId);

                if ($alat->stok < $jumlah) {
                    throw new \Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi.");
                }

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alatId,
                    'jumlah' => $jumlah,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // Mengubah status peminjaman
    public function updateStatusPeminjaman(Request $request, $id)
    {
        $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);

        $request->validate([
            'status' => 'required|in:diajukan,dipinjam,dikembalikan,telat',
        ]);

        DB::beginTransaction();

        try {
            $statusLama = $peminjaman->status;
            $statusBaru = $request->status;

            if ($statusLama === 'diajukan' && $statusBaru === 'dipinjam') {
                foreach ($peminjaman->detailPinjam as $detail) {
                    $alat = Alat::findOrFail($detail->alat_id);

                    if ($alat->stok < $detail->jumlah) {
                        throw new \Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi untuk dipinjam.");
                    }

                    $alat->stok -= $detail->jumlah;
                    $alat->save();
                }
            }

            elseif (($statusLama === 'dipinjam' && $statusBaru === 'dikembalikan') || ($statusLama === 'dipinjam' && $statusBaru === 'telat')) {
                foreach ($peminjaman->detailPinjam as $detail) {
                    $alat = Alat::findOrFail($detail->alat_id);
                    $alat->stok += $detail->jumlah;
                    $alat->save();
                }

                if ($statusBaru === 'dikembalikan') {
                    Pengembalian::create([
                        'peminjaman_id' => $peminjaman->id,
                        'tanggal_kembali' => now(),
                        'kondisi_kembali' => 'bagus',
                        'denda' => 0,
                        'petugas_id' => auth()->id(),
                    ]);
                }
            }

            $peminjaman->update(['status' => $statusBaru]);

            DB::commit();

            return redirect()->route('admin.peminjaman.index')->with('success', 'Status peminjaman berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // Menghapus data peminjaman
    public function destroyPeminjaman($id)
    {
        $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);

        if ($peminjaman->status === 'dipinjam' || $peminjaman->status === 'dikembalikan' || $peminjaman->status === 'telat') {
            return redirect()->route('admin.peminjaman.index')
                ->with('error', 'Peminjaman yang sedang aktif tidak dapat dihapus.');
        }

        $peminjaman->detailPinjam()->delete();
        $peminjaman->delete();

        return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil dihapus.');
    }

    // Menghapus kategori
    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        // Opsional: cek apakah kategori masih dipakai oleh alat
        if ($kategori->alat()->count() > 0) {
            return redirect()->route('admin.kategori.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh data alat.');
        }

        $kategori->delete();

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
