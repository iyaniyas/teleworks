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
        // index gabungan untuk cegah duplikat
        $table->unique(['source', 'source_url'], 'jobs_source_sourceurl_unique');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
        $table->dropUnique('jobs_source_sourceurl_unique');
    });
    }
};
