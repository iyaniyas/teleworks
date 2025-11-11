<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Tambahkan kolom base_salary_string jika belum ada
            if (!Schema::hasColumn('jobs', 'base_salary_string')) {
                $table->string('base_salary_string', 255)
                      ->nullable()
                      ->after('base_salary_unit');
            }

            // Tambahkan kolom employment_type_raw jika belum ada
            if (!Schema::hasColumn('jobs', 'employment_type_raw')) {
                $table->longText('employment_type_raw')
                      ->nullable()
                      ->after('employment_type');
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

