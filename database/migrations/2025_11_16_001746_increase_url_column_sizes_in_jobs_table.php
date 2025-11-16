<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            // ubah VARCHAR → TEXT
            $table->text('identifier_value')->nullable()->change();
            $table->text('final_url')->nullable()->change();
            $table->text('apply_url')->nullable()->change();
            $table->text('source_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            // fallback: VARCHAR(255)
            $table->string('identifier_value', 255)->nullable()->change();
            $table->string('final_url', 255)->nullable()->change();
            $table->string('apply_url', 255)->nullable()->change();
            $table->string('source_url', 255)->nullable()->change();
        });
    }
};

