# Narasi Uji Kompetensi: Setup Aplikasi

Dokumen ini membantu menjelaskan proses menyiapkan aplikasi peminjaman alat menggunakan VS Code, Docker, dan file proyek yang tersedia.

## Narasi Saat Wawancara

"Pertama, saya membuka folder proyek di VS Code dan menggunakan terminal pada folder utama proyek. Saya memeriksa `docker-compose.yml` untuk memahami layanan yang dibutuhkan. Proyek ini menggunakan container MySQL sebagai database, container Laravel sebagai backend, dan phpMyAdmin untuk melihat database melalui browser.

Saya membangun image backend dengan Docker Compose. Dockerfile backend menyediakan PHP 8.4 CLI, ekstensi `pdo_mysql` dan `zip`, serta Composer. Setelah image siap, saya menjalankan container MySQL. Saya kemudian memasang dependency PHP dengan Composer di dalam container Laravel, sehingga saya tidak perlu memasang PHP dan Composer secara terpisah di komputer.

Sebelum menjalankan aplikasi, saya memastikan konfigurasi `.env` menggunakan MySQL dengan host `mysql-server` dan port internal `3306`. Nama host tersebut dipakai karena Laravel dan MySQL berkomunikasi di jaringan Docker. Port `3307` hanya digunakan jika database diakses dari komputer host.

Setelah dependency terpasang, saya menjalankan container Laravel dan phpMyAdmin. Saya menunggu MySQL siap, lalu menjalankan migration. Migration membuat struktur tabel berdasarkan file yang ada di `database/migrations` dan mencatat migration yang sudah dijalankan, sehingga struktur database dapat dikelola secara teratur.

Berikutnya saya menjalankan seeder untuk memasukkan data awal. `DatabaseSeeder` mengatur urutannya: user, kategori, alat, peminjaman, detail peminjaman, pengembalian, lalu log aktivitas. Urutan ini penting karena beberapa data bergantung pada data lain, misalnya alat membutuhkan kategori dan transaksi membutuhkan user serta alat.

Terakhir, saya memeriksa status container, membuka aplikasi di `http://localhost:8000`, dan bila perlu melihat database melalui phpMyAdmin di `http://localhost:8081`. Saya memastikan halaman dapat dibuka dan data awal muncul. Saya tidak menjalankan `migrate:fresh` pada database yang berisi data penting karena perintah itu menghapus seluruh tabel sebelum membuatnya kembali."

## Urutan Perintah

Jalankan perintah dari folder utama proyek, yaitu folder yang berisi `docker-compose.yml`.

### 1. Build image backend

```powershell
docker compose build app-laravel
```

Perintah ini membangun image PHP berdasarkan `backend/Dockerfile`. MySQL dan phpMyAdmin menggunakan image Docker yang didefinisikan di `docker-compose.yml`.

### 2. Jalankan database

```powershell
docker compose up -d mysql-server
docker compose ps
```

Opsi `-d` menjalankan container di latar belakang. Tunggu sampai log MySQL menunjukkan bahwa server siap menerima koneksi sebelum menjalankan migration.

### 3. Install dependency PHP

```powershell
docker compose run --rm --no-deps app-laravel composer install
```

Composer membaca `backend/composer.json` dan memasang package ke folder `backend/vendor`. `--no-deps` mencegah Compose menjalankan ulang service dependency; database sudah dijalankan pada langkah sebelumnya.

### 4. Jalankan aplikasi dan phpMyAdmin

```powershell
docker compose up -d app-laravel phpmyadmin
docker compose ps
```

Pastikan `.env` di folder `backend` sudah mengarah ke service database Docker. Nilai koneksi yang dipakai aplikasi adalah `DB_CONNECTION=mysql`, `DB_HOST=mysql-server`, dan `DB_PORT=3306`, dengan nama database serta user yang sesuai konfigurasi Compose.

Jika file `.env` belum ada, salin dari `.env.example`, lalu sesuaikan konfigurasi database. Buat `APP_KEY` hanya jika nilainya masih kosong:

```powershell
docker compose exec -T app-laravel php artisan key:generate
```

### 5. Jalankan migration

```powershell
docker compose exec -T app-laravel php artisan migrate --force
```

Migration membuat atau memperbarui tabel tanpa menghapus data yang sudah ada. Opsi `--force` diperlukan karena aplikasi berjalan dalam environment Docker non-interaktif.

### 6. Masukkan data awal

```powershell
docker compose exec -T app-laravel php artisan db:seed --force
```

Seeder utama memanggil `UserSeeder`, `KategoriSeeder`, `AlatSeeder`, `PeminjamanSeeder`, `DetailPinjamSeeder`, `PengembalianSeeder`, dan `LogAktivitasSeeder`. Seeder data awal sebaiknya dijalankan satu kali pada database baru. Sebagian seeder menggunakan `create`, sehingga menjalankannya berulang dapat menghasilkan data duplikat atau konflik.

### 7. Verifikasi dan akses aplikasi

```powershell
docker compose ps
docker compose exec -T app-laravel php artisan migrate:status
```

- Aplikasi: `http://localhost:8000`
- phpMyAdmin: `http://localhost:8081`

Jika migration gagal karena koneksi database ditolak, periksa `docker compose ps`, tunggu MySQL siap, lalu ulangi perintah migration.

## Catatan Penting

- Jangan menjalankan `php artisan migrate:fresh --seed` pada database yang datanya ingin dipertahankan. `migrate:fresh` menghapus semua tabel dan data sebelum membuat ulang struktur database.
- Untuk latihan dari database kosong yang memang boleh dihapus, perintah tersebut dapat digunakan sebagai alternatif satu langkah:

```powershell
docker compose exec -T app-laravel php artisan migrate:fresh --seed --force
```

- Database disimpan pada named volume `db_data`, sehingga data tetap ada meskipun container dihentikan atau dibuat ulang. Jangan menghapus volume jika data perlu dipertahankan.
- `docker compose down` menghentikan dan menghapus container, tetapi tidak menghapus named volume. `docker compose down -v` juga menghapus volume database.
- Jika file frontend berubah, project menyediakan Vite melalui `backend/package.json`. Build aset frontend dilakukan dengan Node.js dari folder `backend` menggunakan `npm install` lalu `npm run build`; hal ini terpisah dari build image PHP Docker.

## Pertanyaan Singkat yang Mungkin Ditanyakan

**Apa fungsi Docker Compose di proyek ini?**  
Docker Compose menjalankan dan menghubungkan service Laravel, MySQL, dan phpMyAdmin, termasuk mengatur port, network, dan volume.

**Apa perbedaan build dan install dependency?**  
Build membuat image lingkungan PHP dari Dockerfile. `composer install` memasang package aplikasi yang tercantum di `composer.json` ke folder `vendor`.

**Apa fungsi migration?**  
Migration mendefinisikan perubahan struktur database dalam file yang dapat dilacak dan dijalankan berurutan oleh Laravel.

**Apa fungsi seeder?**  
Seeder mengisi database dengan data awal atau contoh, seperti akun, kategori, alat, dan transaksi.

**Bagaimana memastikan setup berhasil?**  
Saya memeriksa status container, status migration, lalu membuka aplikasi di port 8000 dan memastikan halaman serta data awal dapat ditampilkan.

**Apa yang harus diperhatikan saat menjalankan ulang seeder?**  
Seeder tertentu menambahkan data baru setiap kali dipanggil. Saya memastikan database memang membutuhkan data tersebut dan menghindari menjalankannya berulang pada database yang sudah terisi.