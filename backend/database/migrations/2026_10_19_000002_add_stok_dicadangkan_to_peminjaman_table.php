<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->boolean('stok_dicadangkan')->default(false);
        });

        DB::table('peminjaman')
            ->whereIn('status', ['dipinjam', 'menunggu_pengembalian'])
            ->update(['stok_dicadangkan' => true]);
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropColumn('stok_dicadangkan');
        });
    }
};
