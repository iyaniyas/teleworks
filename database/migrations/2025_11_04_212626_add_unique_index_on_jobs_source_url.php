<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
        if (!Schema::hasColumn('jobs', 'source')) $table->string('source')->nullable()->index();
        if (!Schema::hasColumn('jobs', 'source_url')) $table->text('source_url')->nullable();
        $table->unique(['source', 'source_url']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
     Schema::table('jobs', function (Blueprint $table) {
        $table->dropUnique(['source', 'source_url']);
    });
    }
};
