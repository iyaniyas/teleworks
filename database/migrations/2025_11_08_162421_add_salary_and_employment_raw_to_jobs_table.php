<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Tambahkan base_salary_string untuk teks gaji lengkap (misal "$2k–$4k/mo")
            if (!Schema::hasColumn('jobs', 'base_salary_string')) {
                $table->string('base_salary_string', 255)->nullable()->after('base_salary_unit');
            }

            // Tambahkan employment_type_raw untuk menyimpan array JSON employment_statuses
            if (!Schema::hasColumn('jobs', 'employment_type_raw')) {
                $table->longText('employment_type_raw')->nullable()->after('employment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['base_salary_string', 'employment_type_raw']);
        });
    }
};

