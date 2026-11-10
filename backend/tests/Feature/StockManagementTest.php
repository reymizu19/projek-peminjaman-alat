<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPinjam;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_is_reserved_on_request_and_restored_after_return(): void
    {
        $peminjam = $this->createUser('peminjam');
        $petugas = $this->createUser('petugas');
        $alat = $this->createAlat(10);

        $this->actingAs($peminjam)->post(route('peminjam.peminjaman.ajukan'), [
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'alat_id' => [$alat->id],
            'jumlah' => [$alat->id => 3],
            'alasan' => 'Kegiatan belajar',
        ])->assertRedirect(route('peminjam.peminjaman.saya'));

        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 7]);

        $peminjaman = Peminjaman::firstOrFail();
        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $peminjam->id,
            'aktivitas' => "Peminjam {$peminjam->name} mengajukan peminjaman #{$peminjaman->id}.",
        ]);

        $this->actingAs($petugas)
            ->post(route('petugas.peminjaman.setujui', $peminjaman->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 7]);
        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $petugas->id,
            'aktivitas' => "Petugas {$petugas->name} menyetujui peminjaman #{$peminjaman->id} milik {$peminjam->name}.",
        ]);

        $this->actingAs($peminjam)
            ->post(route('peminjam.peminjaman.kembalikan', $peminjaman->id))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $peminjam->id,
            'aktivitas' => "Peminjam {$peminjam->name} mengajukan pengembalian untuk peminjaman #{$peminjaman->id}.",
        ]);

        $this->actingAs($petugas)
            ->post(route('petugas.pengembalian.terima', $peminjaman->id), [
                'kondisi_kembali' => 'Baik',
                'denda' => 0,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $petugas->id,
            'aktivitas' => "Petugas {$petugas->name} menerima pengembalian peminjaman #{$peminjaman->id} milik {$peminjam->name}; kondisi: Baik; denda: Rp 0; status akhir: dikembalikan.",
        ]);
        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 10]);
        $this->assertDatabaseHas('peminjaman', [
            'id' => $peminjaman->id,
            'status' => 'dikembalikan',
            'stok_dicadangkan' => false,
        ]);
    }

    public function test_admin_can_view_paginated_activity_history(): void
    {
        $admin = $this->createUser('admin');
        $this->actingAs($admin);

        for ($index = 1; $index <= 21; $index++) {
            $timestamp = now()->addSeconds($index);
            $admin->log_aktivitas()->create([
                'aktivitas' => $index === 1 ? 'Log lama unik' : "Aktivitas {$index}",
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $this->get(route('admin.log-aktivitas.index'))
            ->assertOk()
            ->assertSee('Riwayat Log Aktivitas')
            ->assertSee('Aktivitas 21')
            ->assertDontSee('Log lama unik');
    }

    public function test_request_without_selected_equipment_shows_a_clear_validation_message(): void
    {
        $peminjam = $this->createUser('peminjam');
        $alat = $this->createAlat(5);

        $this->actingAs($peminjam)->from(route('peminjam.katalog'))->post(route('peminjam.peminjaman.ajukan'), [
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'alat_id' => [],
            'jumlah' => [$alat->id => 1],
            'alasan' => 'Kegiatan belajar',
        ])->assertRedirect(route('peminjam.katalog'))
            ->assertSessionHasErrors([
                'alat_id' => 'Pilih minimal satu alat yang ingin dipinjam.',
            ]);

        $this->assertDatabaseCount('peminjaman', 0);
    }

    public function test_rejecting_a_pending_request_restores_reserved_stock(): void
    {
        $peminjam = $this->createUser('peminjam');
        $petugas = $this->createUser('petugas');
        $alat = $this->createAlat(5);

        $this->actingAs($peminjam)->post(route('peminjam.peminjaman.ajukan'), [
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'alat_id' => [$alat->id],
            'jumlah' => [$alat->id => 2],
            'alasan' => 'Kegiatan belajar',
        ]);

        $peminjaman = Peminjaman::firstOrFail();
        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 3]);

        $this->actingAs($petugas)
            ->post(route('petugas.peminjaman.tolak', $peminjaman->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 5]);
        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $petugas->id,
            'aktivitas' => "Petugas {$petugas->name} menolak pengajuan peminjaman #{$peminjaman->id} milik {$peminjam->name}.",
        ]);
    }

    public function test_editing_and_cancelling_a_pending_request_adjusts_reserved_stock(): void
    {
        $peminjam = $this->createUser('peminjam');
        $alat = $this->createAlat(5);

        $this->actingAs($peminjam)->post(route('peminjam.peminjaman.ajukan'), [
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'alat_id' => [$alat->id],
            'jumlah' => [$alat->id => 2],
            'alasan' => 'Kegiatan belajar',
        ]);

        $peminjaman = Peminjaman::firstOrFail();
        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 3]);

        $this->actingAs($peminjam)
            ->put(route('peminjam.peminjaman.update', $peminjaman->id), [
                'alat_id' => [$alat->id],
                'jumlah' => [$alat->id => 4],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 1]);

        $this->actingAs($peminjam)
            ->delete(route('peminjam.peminjaman.destroy', $peminjaman->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 5]);
    }

    public function test_approval_still_reserves_stock_for_legacy_pending_requests(): void
    {
        $peminjam = $this->createUser('peminjam');
        $petugas = $this->createUser('petugas');
        $alat = $this->createAlat(5);
        $peminjaman = Peminjaman::create([
            'user_id' => $peminjam->id,
            'tanggal_pinjam' => now(),
            'tanggal_kembali_plan' => now()->addDays(3),
            'status' => 'diajukan',
            'stok_dicadangkan' => false,
        ]);
        DetailPinjam::create([
            'peminjaman_id' => $peminjaman->id,
            'alat_id' => $alat->id,
            'jumlah' => 2,
        ]);

        $this->actingAs($petugas)
            ->post(route('petugas.peminjaman.setujui', $peminjaman->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 3]);
        $this->assertDatabaseHas('peminjaman', ['id' => $peminjaman->id, 'stok_dicadangkan' => true]);
    }

    public function test_api_request_reserves_stock_before_approval(): void
    {
        $peminjam = $this->createUser('peminjam');
        $petugas = $this->createUser('petugas');
        $alat = $this->createAlat(5);

        $response = $this->actingAs($peminjam, 'sanctum')->postJson('/api/peminjaman', [
            'tgl_kembali_plan' => now()->addDays(3)->toDateString(),
            'items' => [
                ['alat_id' => $alat->id, 'jumlah' => 2],
            ],
        ])->assertCreated();

        $peminjamanId = $response->json('data.id');
        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 3]);

        $this->actingAs($petugas, 'sanctum')
            ->postJson("/api/peminjaman/{$peminjamanId}/approve")
            ->assertOk();

        $this->assertDatabaseHas('alat', ['id' => $alat->id, 'stok' => 3]);
    }

    private function createAlat(int $stok): Alat
    {
        $kategori = Kategori::create(['nama_kategori' => 'Elektronik']);

        return Alat::create([
            'kategori_id' => $kategori->id,
            'nama_alat' => 'Proyektor',
            'stok' => $stok,
            'status_kondisi' => 'Baik',
        ]);
    }

    private function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $role.'@example.test',
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
