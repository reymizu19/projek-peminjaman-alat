<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pengembalian', 'badge_color')) {
            Schema::table('pengembalian', function (Blueprint $table) {
                $table->dropColumn('badge_color');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pengembalian', 'badge_color')) {
            Schema::table('pengembalian', function (Blueprint $table) {
                $table->string('badge_color')->default('neutral')->after('kondisi_kembali');
            });
        }
    }
};