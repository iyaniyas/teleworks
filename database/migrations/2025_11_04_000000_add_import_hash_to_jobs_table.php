<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'import_hash')) {
                $table->string('import_hash', 64)->nullable()->unique()->after('is_imported');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'import_hash')) {
                $table->dropUnique(['import_hash']);
                $table->dropColumn('import_hash');
            }
        });
    }
};

