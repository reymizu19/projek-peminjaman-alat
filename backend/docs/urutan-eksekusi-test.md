# Urutan Eksekusi Test Case

Gunakan ID dari [test-cases-qa.tsv](test-cases-qa.tsv). Jalankan di database testing atau dengan record khusus QA, bukan data transaksi pengguna. Urutan ini mengikuti dependensi: admin menyiapkan data, peminjam mengajukan, petugas memproses, lalu hasil diverifikasi kembali.

## A. Smoke Test Web

1. Login dan logout: `AUTH-WEB-001` sampai `AUTH-WEB-008`.
2. Uji akses per role dan akses tanpa login: `RBAC-WEB-001` sampai `RBAC-WEB-005`.
3. Pastikan halaman utama tiap role dapat dibuka sebelum mulai mengubah data.

## B. Persiapan Data Oleh Admin

1. Dashboard dan daftar awal: `ADMIN-001`, `ADMIN-002`, `ADMIN-008`, `ADMIN-014`, `ADMIN-019`, `ADMIN-027`.
2. Siapkan akun QA: `ADMIN-003` sampai `ADMIN-006`.
3. Siapkan kategori QA: `ADMIN-009` sampai `ADMIN-011`.
4. Siapkan alat QA dengan stok cukup: `ADMIN-015` sampai `ADMIN-017`.
5. Jalankan validasi dan kasus penolakan master data: `ADMIN-005`, `ADMIN-010`, `ADMIN-012`, `ADMIN-016`.
6. Jalankan pembuatan transaksi oleh admin bila diperlukan: `ADMIN-020` sampai `ADMIN-022`.
7. Jangan jalankan penghapusan kategori, alat, atau user yang masih diperlukan alur berikutnya. Kasus hapus master data (`ADMIN-007`, `ADMIN-013`, `ADMIN-018`) gunakan record QA terpisah dan jalankan pada tahap pembersihan.

## C. Alur Peminjam Membuat Pengajuan

1. Login sebagai peminjam dan cek katalog: `PEMINJAM-001`, `PEMINJAM-002`.
2. Buat pengajuan valid yang akan diteruskan ke petugas: `PEMINJAM-003`.
3. Uji validasi tanggal, pilihan/jumlah alat, dan alasan dengan request terpisah: `PEMINJAM-004` sampai `PEMINJAM-006`.
4. Cek daftar pinjaman dan edit pengajuan milik sendiri: `PEMINJAM-007` sampai `PEMINJAM-010`.
5. Jalankan penghapusan dan uji kepemilikan menggunakan transaksi QA yang tidak dipakai lagi: `PEMINJAM-011`, `PEMINJAM-012`.

## D. Alur Petugas Menyetujui dan Memproses Pengembalian

1. Login sebagai petugas; cari pengajuan yang dibuat pada tahap C: `PETUGAS-001`.
2. Setujui pengajuan tersebut: `PETUGAS-002`; pastikan status menjadi `dipinjam` dan stok berkurang sesuai detail.
3. Uji penolakan dan status yang tidak sesuai pada pengajuan QA lain: `PETUGAS-003`, `PETUGAS-004`.
4. Peminjam mengajukan pengembalian atas transaksi yang disetujui: `PEMINJAM-013`.
5. Petugas cek antrean pengembalian: `PETUGAS-005`, lalu terima pengembalian: `PETUGAS-006` atau `PETUGAS-007` sesuai tanggal jatuh tempo.
6. Coba validasi dan pengajuan pengembalian ganda pada transaksi QA terpisah: `PETUGAS-008`.

## E. Verifikasi Hasil Peminjam dan Admin

1. Login lagi sebagai peminjam; cek riwayat transaksi selesai: `PEMINJAM-015`.
2. Periksa transaksi user lain dan aksi yang tidak berhak: `PEMINJAM-014`.
3. Uji profil dan unggah foto: `PEMINJAM-016`, `PEMINJAM-017`.
4. Login sebagai admin; cek daftar pengembalian dan edit denda/kondisi: `ADMIN-028`, `ADMIN-029`.
5. Uji perubahan status admin pada transaksi QA terpisah: `ADMIN-023`, `ADMIN-024`.
6. Uji penghapusan transaksi dan pembatalan pengembalian dengan record khusus: `ADMIN-025`, `ADMIN-026`, `ADMIN-030`, `ADMIN-031`.
7. Jalankan rekap dan cetak PDF sebagai petugas: `PETUGAS-009` sampai `PETUGAS-013`.
8. Akhiri dengan kasus hapus master data dan cek log/relasi pada data QA: `ADMIN-007`, `ADMIN-013`, `ADMIN-018`.

## F. API

1. Registrasi, login, token, dan logout: `API-AUTH-001` sampai `API-AUTH-005`.
2. Uji akses tanpa token dan role silang: `API-RBAC-001`, `API-RBAC-002`.
3. Admin siapkan kategori, alat, dan user QA: `API-ADMIN-001` sampai `API-ADMIN-010`.
4. Admin cek daftar/detail peminjaman: `API-ADMIN-011`.
5. Peminjam cek katalog dan membuat pengajuan: `API-PEMINJAM-001` sampai `API-PEMINJAM-004`.
6. Petugas menyetujui dan memproses pengembalian: `API-PETUGAS-001` sampai `API-PETUGAS-003`.
7. Peminjam cek riwayat: `API-PEMINJAM-005`.
8. Petugas uji laporan dan filter: `API-PETUGAS-004`, `API-PETUGAS-005`.
9. Admin verifikasi peminjaman, pengembalian, dan log: `API-ADMIN-012` sampai `API-ADMIN-017`.
10. Jalankan uji rollback, request bersamaan, audit trail, cascade, CSRF, mass assignment, dan IDOR paling akhir pada environment terisolasi: `API-CROSS-001` sampai `API-CROSS-005`.

## Catatan Data Uji

- Siapkan minimal satu akun QA untuk setiap role, satu kategori QA, dua alat QA dengan stok berbeda, dan transaksi untuk status `diajukan`, `dipinjam`, `menunggu_pengembalian`, `dikembalikan`, serta `telat`.
- Simpan satu transaksi valid dari tahap C khusus untuk alur persetujuan dan pengembalian. Jangan gunakan transaksi yang sama untuk kasus hapus, pengembalian ganda, atau rollback.
- Untuk test kasus tanggal terlambat, gunakan tanggal rencana kembali sebelum tanggal eksekusi test. Untuk kasus tepat waktu, gunakan tanggal hari ini atau tanggal masa depan.
- Catat hasil aktual dan `PASS`/`FAIL` di dua kolom terakhir TSV. Setelah test mutatif, verifikasi ulang status, stok, detail alat, return, dan log sebelum lanjut.