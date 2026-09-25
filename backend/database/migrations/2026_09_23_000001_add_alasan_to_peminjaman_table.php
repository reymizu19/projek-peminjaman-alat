<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('peminjaman', 'alasan')) {
            Schema::table('peminjaman', function (Blueprint $table) {
                $table->string('alasan', 500)->nullable()->after('tanggal_kembali_plan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('peminjaman', 'alasan')) {
            Schema::table('peminjaman', function (Blueprint $table) {
                $table->dropColumn('alasan');
            });
        }
    }
};