# Alur Logika Admin, Petugas, dan Peminjam

Dokumen ini menjelaskan alur aplikasi peminjaman alat dengan bahasa sederhana. Fokus utamanya adalah alur web Laravel yang memakai login dan session.

## Rumus Cepat

Ingat urutan ini:

**Admin menyiapkan -> Peminjam mengajukan -> Petugas memproses -> Peminjam mengembalikan -> Petugas memeriksa -> Sistem mencatat.**

Kunci stok: **stok berkurang saat pengajuan dibuat, lalu bertambah lagi setelah pengembalian diterima atau pengajuan ditolak.**

## 1. Awal: Login dan Pembagian Hak

1. Pengguna membuka halaman login dan mengisi email serta password.
2. Laravel memeriksa kecocokan akun.
3. Jika benar, pengguna diarahkan ke halaman sesuai role.
4. Middleware memeriksa role setiap kali halaman khusus dibuka. Jika role tidak sesuai, akses ditolak.

Potongan login dari `app/Http/Controllers/AuthController.php`:

```php
if (Auth::attempt($credentials)) {
    $request->session()->regenerate();
    $user = Auth::user();

    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($user->role === 'petugas') {
        return redirect()->route('petugas.peminjaman.index');
    } elseif ($user->role === 'peminjam') {
        return redirect()->route('peminjam.katalog');
    }
}
```

Contoh pembatasan halaman dari `routes/web.php`:

```php
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')->group(function () {
        // Hanya admin yang boleh masuk ke halaman ini.
    });

Route::middleware(['auth', 'role:petugas,admin'])
    ->prefix('petugas')->group(function () {
        // Petugas dan admin boleh memproses transaksi.
    });

Route::middleware(['auth', 'role:peminjam'])
    ->prefix('peminjam')->group(function () {
        // Peminjam hanya mengakses halaman miliknya.
    });
```

## 2. Alur Admin: Menyiapkan dan Mengawasi

Admin bertugas menyiapkan data agar peminjaman dapat berjalan.

1. Admin masuk ke dashboard dan melihat ringkasan serta log aktivitas.
2. Admin membuat kategori alat.
3. Admin menambah alat, stok, kondisi, deskripsi, dan gambar.
4. Admin membuat akun dengan role `admin`, `petugas`, atau `peminjam`.
5. Admin dapat melihat semua peminjaman dan pengembalian.
6. Admin dapat membuat peminjaman atas nama peminjam, memperbarui status transaksi, dan memperbaiki data pengembalian.

Halaman admin utama didefinisikan di `routes/web.php`; proses datanya berada di `app/Http/Controllers/AdminController.php`.

Cara mudah mengingat tugas admin: **data, akun, pantau, koreksi**.

## 3. Alur Peminjam: Memilih sampai Mengajukan Kembali

1. Peminjam masuk dan melihat katalog alat.
2. Peminjam memilih alat, jumlah, alasan, dan tanggal rencana kembali.
3. Sistem memeriksa input dan stok.
4. Jika stok cukup, sistem membuat transaksi berstatus `diajukan`, menyimpan rincian alat, lalu mengurangi stok.
5. Peminjam dapat melihat pengajuan di menu **Peminjaman Saya**.
6. Selama status masih `diajukan`, peminjam dapat mengubah atau membatalkan pengajuan. Jika dibatalkan, stok yang dicadangkan dikembalikan.
7. Setelah barang dipinjam, peminjam menekan tombol ajukan pengembalian. Status berubah menjadi `menunggu_pengembalian`.
8. Setelah diperiksa petugas, transaksi selesai dan masuk ke riwayat.

Potongan inti pengajuan dari `app/Http/Controllers/PeminjamController.php`:

```php
$peminjaman = Peminjaman::create([
    'user_id' => auth()->id(),
    'tanggal_pinjam' => now(),
    'tanggal_kembali_plan' => $tanggalKembaliPlan,
    'status' => 'diajukan',
    'stok_dicadangkan' => true,
]);

$alat = Alat::lockForUpdate()->findOrFail($alatId);
$alat->decrement('stok', $jumlah);
```

`lockForUpdate()` mengunci data stok selama transaksi agar dua pengajuan bersamaan tidak memakai stok yang sama.

Permintaan pengembalian hanya boleh dibuat oleh pemilik transaksi dan saat status masih `dipinjam`:

```php
abort_unless($peminjaman->user_id === auth()->id(), 403);

if ($peminjaman->status !== 'dipinjam') {
    return redirect()->back()->with('error', 'Peminjaman tidak dapat diajukan untuk dikembalikan.');
}

$peminjaman->update(['status' => 'menunggu_pengembalian']);
```

Cara mudah mengingat tugas peminjam: **lihat, pilih, ajukan, pinjam, minta kembali**.

## 4. Alur Petugas: Memutuskan dan Memeriksa

### Memproses pengajuan

1. Petugas membuka daftar pengajuan berstatus `diajukan`.
2. Petugas memeriksa nama peminjam, alat, jumlah, dan tanggal kembali.
3. Jika disetujui, status menjadi `dipinjam`.
4. Jika ditolak, pengajuan dihapus dan stok cadangan dikembalikan.

Karena stok sudah dicadangkan ketika peminjam mengajukan, persetujuan normal **tidak mengurangi stok untuk kedua kalinya**. Kode juga mengecek flag `stok_dicadangkan` untuk menangani data lama yang belum mencadangkan stok.

### Memproses pengembalian

1. Peminjam mengajukan pengembalian, sehingga status menjadi `menunggu_pengembalian`.
2. Petugas memeriksa kondisi barang dan mengisi denda bila ada.
3. Sistem membandingkan tanggal hari ini dengan tanggal rencana kembali.
4. Jika melewati tanggal, status akhir `telat`; jika tidak, status akhir `dikembalikan`.
5. Sistem menyimpan data pengembalian dan menambahkan stok kembali.

Potongan inti dari `app/Http/Controllers/PetugasController.php`:

```php
if ($peminjaman->status !== 'menunggu_pengembalian') {
    throw new Exception("Pengembalian hanya dapat diterima setelah peminjam mengajukannya. Status saat ini: '{$peminjaman->status}'.");
}

$statusPeminjamanBaru = $hariIni->greaterThan($tglKembaliPlan)
    ? 'telat'
    : 'dikembalikan';

$peminjaman->update([
    'status' => $statusPeminjamanBaru,
    'stok_dicadangkan' => false,
]);

foreach ($peminjaman->detailPinjams as $detail) {
    Alat::lockForUpdate()->findOrFail($detail->alat_id)
        ->increment('stok', $detail->jumlah);
}
```

Petugas juga dapat mengirim pengingat pengembalian ketika tanggal kembali sudah tiba, membuka laporan, memfilter laporan, dan mengunduh laporan PDF.

Cara mudah mengingat tugas petugas: **setujui/tolak, periksa, tentukan telat/tidak, kembalikan stok, laporkan**.

## 5. Urutan Status Transaksi

```text
diajukan
  |-- disetujui petugas --> dipinjam
  |-- ditolak petugas ----> pengajuan dihapus

dipinjam
  |-- peminjam ajukan kembali --> menunggu_pengembalian

menunggu_pengembalian
  |-- diperiksa tepat waktu --> dikembalikan
  |-- diperiksa terlambat ---> telat
```

Status yang disimpan pada aplikasi: `diajukan`, `dipinjam`, `menunggu_pengembalian`, `dikembalikan`, dan `telat`.

## 6. Ringkasan Stok

| Kejadian | Perubahan stok |
| --- | --- |
| Peminjam mengajukan dan stok cukup | Stok berkurang |
| Petugas menyetujui pengajuan normal | Tidak berubah lagi |
| Petugas menolak pengajuan | Stok bertambah kembali |
| Peminjam membatalkan pengajuan yang masih diajukan | Stok bertambah kembali |
| Petugas menerima pengembalian | Stok bertambah kembali |

Aturan ini dijalankan di dalam transaksi database supaya perubahan status, rincian, dan stok tetap selaras.

## 7. Log Aktivitas dan Logout

Aktivitas alur utama dicatat langsung saat aksi berhasil, baik melalui web maupun API: pengajuan peminjaman, persetujuan atau penolakan petugas, pengajuan pengembalian, dan penerimaan pengembalian. Log menyebut pelaku dan nomor transaksi; log penerimaan juga menyimpan kondisi, denda, dan status akhir. Pencatatan dilakukan bersama transaksi agar log tidak tertinggal jika perubahan stok atau status gagal. Dashboard menampilkan sepuluh aktivitas terbaru, sedangkan menu **Log Aktivitas** menampilkan seluruh riwayat dengan pagination 20 data per halaman.

Saat logout, Laravel mengakhiri session dan membuat token session baru:

```php
Auth::logout();
$request->session()->invalidate();
$request->session()->regenerateToken();
```

## Naskah Singkat untuk Dihafal

"Admin menyiapkan kategori, alat, stok, dan akun. Peminjam memilih alat lalu mengajukan pinjaman. Saat pengajuan dibuat, stok langsung dicadangkan dan statusnya menjadi diajukan. Petugas menyetujui agar status menjadi dipinjam, atau menolak dan mengembalikan stok. Setelah selesai, peminjam mengajukan pengembalian. Petugas memeriksa kondisi dan denda, menentukan apakah pengembalian tepat waktu atau terlambat, lalu sistem menambah stok dan menyimpan riwayat. Aktivitas penting tercatat dan admin dapat memantau laporan."

## Catatan API

Selain halaman web, aplikasi juga memiliki route API di `routes/api.php`. API memakai token Bearer Sanctum, tetapi prinsip alurnya tetap serupa. Registrasi API membuat akun dengan role `peminjam`; akun admin dan petugas dikelola melalui fitur user admin.

## Pertanyaan Penting Saat Presentasi

### Kenapa gambar alat yang dulu tidak ada sekarang bisa tampil?

Karena sekarang formulir admin menyediakan unggah gambar, controller menyimpan file di disk `public`, lalu halaman katalog menampilkan URL file tersebut. Jika belum ada gambar atau file tidak bisa dibuka, halaman memakai gambar placeholder.

Lokasi kode:

- Form unggah: `resources/views/admin/alat/create.blade.php`
- Simpan gambar: `app/Http/Controllers/AdminController.php`
- Tampilkan gambar: `resources/views/peminjam/katalog.blade.php` dan `resources/views/admin/alat/index.blade.php`
- Pengaturan disk publik: `config/filesystems.php`
- File gambar tersimpan di `storage/app/public/alat/` dan diakses melalui `public/storage/`.

Potongan simpan dan tampil:

```php
// app/Http/Controllers/AdminController.php
$data['gambar'] = $request->file('gambar')->store('alat', 'public');
```

```blade
{{-- resources/views/peminjam/katalog.blade.php --}}
<img src="{{ asset('storage/' . $alat->gambar) }}" alt="{{ $alat->nama_alat }}">
```

Kolom `gambar` menyimpan lokasi file, bukan isi gambarnya. Agar URL `/storage/...` bekerja, Laravel perlu memiliki symbolic link `public/storage` ke `storage/app/public` (`php artisan storage:link`). Gambar boleh kosong; karena itu placeholder tetap disediakan.

Jawaban singkat: **"Admin mengunggah gambar, Laravel menyimpannya di disk publik, lalu katalog menampilkan path gambar dari database. Kalau tidak ada gambar, muncul placeholder."**

### Kenapa stok yang dulu tidak terlihat sekarang tampil?

Karena halaman katalog sekarang menambahkan kolom **Stok** dan membaca nilainya dari `$alat->stok`. Nilai itu berasal dari kolom integer `stok` di tabel `alat`, yang diisi atau diperbarui admin. Jumlah yang bisa dipinjam juga dibatasi oleh stok yang tersedia.

Lokasi kode:

- Kolom database: `database/migrations/2026_07_27_004838_03_create_alat_table.php`
- Form stok admin: `resources/views/admin/alat/create.blade.php`
- Tampilkan stok dan batas jumlah: `resources/views/peminjam/katalog.blade.php`
- Perubahan stok transaksi: `app/Http/Controllers/PeminjamController.php`

Potongan penting:

```php
// database/migrations/2026_07_27_004838_03_create_alat_table.php
$table->integer('stok')->default(0);
```

```blade
{{-- resources/views/peminjam/katalog.blade.php --}}
<th>Stok</th>
<td>{{ $alat->stok }}</td>
<input type="number" min="1" max="{{ $alat->stok }}"
    {{ $alat->stok < 1 ? 'disabled' : '' }}>
```

Maksudnya, angka **Stok** di katalog menunjukkan jumlah alat yang masih tersedia untuk diajukan, bukan jumlah seluruh alat yang pernah dimiliki:

1. **Peminjam mengirim pengajuan:** jumlah alat yang diminta langsung dicadangkan dengan mengurangi `alat.stok`. Contoh: stok 10 dan peminjam mengajukan 3, angka yang tersedia menjadi 7. Petugas menyetujui pengajuan tidak mengurangi stok lagi karena jumlahnya sudah dicadangkan.
2. **Pengajuan ditolak:** jumlah yang sudah dicadangkan ditambahkan kembali. Dari contoh tadi, stok kembali dari 7 menjadi 10.
3. **Barang dikembalikan:** stok baru ditambahkan kembali setelah petugas menerima/memproses pengembalian. Ketika peminjam baru mengajukan pengembalian dan statusnya masih menunggu pemeriksaan, stok tetap dicadangkan (tetap 7 pada contoh).

Kode dan direktori terkait:

- `backend/app/Http/Controllers/PeminjamController.php` — pengajuan mengurangi stok; perubahan jumlah atau pembatalan pengajuan menyesuaikan/mengembalikan stok.
- `backend/app/Http/Controllers/PetugasController.php` — penolakan pengajuan dan penerimaan pengembalian mengembalikan stok.
- `backend/app/Http/Controllers/API/PeminjamanController.php` dan `backend/app/Http/Controllers/API/PengembalianController.php` — alur yang sama untuk permintaan melalui API.
- `backend/resources/views/peminjam/katalog.blade.php` — menampilkan nilai stok yang tersedia pada katalog.
- `backend/database/migrations/2026_07_27_004838_03_create_alat_table.php` — membuat kolom `alat.stok`; `backend/database/migrations/2026_10_19_000002_add_stok_dicadangkan_to_peminjaman_table.php` — menandai apakah stok pengajuan sudah dicadangkan agar tidak dikurangi atau dikembalikan dua kali.
- `backend/tests/Feature/StockManagementTest.php` — menguji pengurangan saat pengajuan, pengembalian stok setelah penolakan/pengembalian, serta alur API.

Jadi, angka katalog selalu mengikuti stok tersedia yang tersimpan di database. **Contoh singkat:** stok awal 10, ajukan 3 → tersedia 7; ditolak → kembali 10; atau disetujui lalu barang dikembalikan dan diterima petugas → kembali 10.