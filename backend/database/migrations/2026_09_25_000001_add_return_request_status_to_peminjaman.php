<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->enum('status', ['diajukan', 'dipinjam', 'menunggu_pengembalian', 'dikembalikan', 'telat'])->change();
            $table->timestamp('pengembalian_diajukan_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropColumn('pengembalian_diajukan_at');
            $table->enum('status', ['diajukan', 'dipinjam', 'dikembalikan', 'telat'])->change();
        });
    }
};