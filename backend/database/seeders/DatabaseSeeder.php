<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            KategoriSeeder::class,
            AlatSeeder::class,
            PeminjamanSeeder::class,
            DetailPinjamSeeder::class,
            PengembalianSeeder::class,
            LogAktivitasSeeder::class,
        ]);
    }
}
