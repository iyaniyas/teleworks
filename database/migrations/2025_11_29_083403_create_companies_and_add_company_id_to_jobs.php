<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kalau table companies belum ada, baru buat
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->string('domain')->nullable();
                $table->string('logo_path')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_suspended')->default(false);
                $table->timestamps();
            });
        }

        // Tambah kolom company_id ke jobs
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('companies')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Hapus relasi & kolom di jobs
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });

        // ⚠ Kalau kamu takut table companies sudah ada dari sistem lama,
        // baris ini boleh DIHAPUS supaya rollback tidak menghapus table itu.
        if (Schema::hasTable('companies')) {
            Schema::drop('companies');
            // atau: Schema::dropIfExists('companies');
        }
    }
};

