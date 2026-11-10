# Tutorial Fitur Register

Dokumen ini menjelaskan alur registrasi akun peminjam pada API Laravel proyek peminjaman alat, mulai dari validasi sampai mencoba endpoint.

## 1. Alur Registrasi

Client mengirim data ke `POST /api/register`. Laravel memvalidasi data, membuat akun dengan role `peminjam`, menyimpan password dalam bentuk hash, lalu menerbitkan token Sanctum. Token tersebut dipakai untuk mengakses endpoint yang membutuhkan login.

```text
Client -> route /api/register -> RegisterRequest -> AuthController
       -> tabel users -> token Sanctum + respons JSON
```

## 2. Pastikan Database Siap

Jalankan dari folder utama proyek, yaitu folder yang berisi `docker-compose.yml`:

```powershell
docker compose up -d mysql-server app-laravel
docker compose exec -T app-laravel php artisan migrate --force
```

Laravel API dapat diakses melalui `http://localhost:8000`. Jika menjalankan Laravel tanpa Docker, gunakan alamat dan port server lokal yang sedang dipakai.

## 3. Daftarkan Route Publik

Di `routes/api.php`, route register tidak ditempatkan dalam middleware `auth:sanctum`, karena pengguna belum memiliki token:

```php
Route::post('/register', [AuthController::class, 'register']);
```

Laravel memberi prefix `/api` pada route API, jadi URL lengkapnya adalah `http://localhost:8000/api/register`.

## 4. Validasi Input

`app/Http/Requests/Auth/RegisterRequest.php` mengatur aturan berikut:

| Field | Aturan |
| --- | --- |
| `name` | Wajib, teks, maksimal 255 karakter |
| `email` | Wajib, format email, maksimal 255 karakter, belum pernah digunakan |
| `password` | Wajib, minimal 8 karakter, harus cocok dengan konfirmasi |
| `password_confirmation` | Wajib dikirim dan harus sama dengan `password` karena aturan `confirmed` |
| `no_hp` | Opsional, teks, maksimal 15 karakter |
| `alamat` | Opsional, teks |

Aturan Laravel `confirmed` mencari field bernama `password_confirmation` secara otomatis.

## 5. Buat Akun dan Token

Di `app/Http/Controllers/API/AuthController.php`, method `register` menjalankan proses utama:

1. Membuat user melalui transaksi database.
2. Mengubah password menjadi hash menggunakan `Hash::make`.
3. Mengatur role sebagai `peminjam` di server. Jangan menerima role dari input client, agar pendaftar tidak dapat membuat akun admin atau petugas.
4. Membuat token menggunakan Sanctum.
5. Mengirim respons JSON dengan status HTTP `201 Created`.

`UserResource` hanya mengirim data profil yang aman untuk ditampilkan. Password tidak dikirim kembali.

## 6. Coba dengan Postman

1. Buat request baru dengan method `POST` dan URL `http://localhost:8000/api/register`.
2. Pada tab **Headers**, tambahkan `Accept: application/json`.
3. Pada tab **Body**, pilih **raw** lalu format **JSON**.
4. Kirim data contoh berikut:

```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "password": "rahasia123",
  "password_confirmation": "rahasia123",
  "no_hp": "081234567890",
  "alamat": "Jl. Melati No. 10"
}
```

Email harus belum pernah digunakan. Ubah email jika request ini sudah pernah berhasil dijalankan.

## 7. Coba dari PowerShell

```powershell
$body = @{
    name = 'Budi Santoso'
    email = 'budi@example.com'
    password = 'rahasia123'
    password_confirmation = 'rahasia123'
    no_hp = '081234567890'
    alamat = 'Jl. Melati No. 10'
} | ConvertTo-Json

Invoke-RestMethod `
    -Method Post `
    -Uri 'http://localhost:8000/api/register' `
    -Headers @{ Accept = 'application/json' } `
    -ContentType 'application/json' `
    -Body $body
```

## 8. Periksa Respons

Jika berhasil, API mengembalikan status `201` dengan bentuk respons seperti berikut:

```json
{
  "message": "Registrasi berhasil.",
  "data": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "role": "peminjam",
    "no_hp": "081234567890",
    "alamat": "Jl. Melati No. 10",
    "foto_profil": null,
    "bergabung_sejak": "2026-10-20"
  },
  "access_token": "token-dari-server",
  "token_type": "Bearer"
}
```

Simpan `access_token` di client dengan penyimpanan yang sesuai. Untuk endpoint terlindungi, kirim header:

```text
Authorization: Bearer token-dari-server
```

Jangan menaruh token atau password ke log, URL, maupun kode yang dibagikan. Gunakan HTTPS saat aplikasi dipasang di server publik.

## 9. Menangani Kesalahan Umum

- **Status `422 Unprocessable Content`**: periksa format email, field wajib, panjang password, dan kecocokan `password_confirmation`. Email yang sudah terdaftar juga ditolak.
- **Status `404 Not Found`**: pastikan URL menggunakan prefix `/api`, yaitu `/api/register`.
- **Koneksi gagal**: pastikan container Laravel dan database berjalan, serta migration sudah dijalankan.
- **Status `500`**: periksa log Laravel di `backend/storage/logs` dan konfigurasi koneksi database.

## 10. File yang Mengatur Fitur

- `routes/api.php`: deklarasi endpoint publik register.
- `app/Http/Requests/Auth/RegisterRequest.php`: aturan validasi.
- `app/Http/Controllers/API/AuthController.php`: pembuatan user, hash password, dan token.
- `app/Http/Resources/UserResource.php`: data profil yang dikirim ke client.
- `app/Models/User.php`: model user dan dukungan token Sanctum.
- `database/migrations/0001_01_01_000000_create_users_table.php`: struktur tabel `users`.