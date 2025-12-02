<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'verification_note')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->text('verification_note')->nullable()->after('is_verified');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'verification_note')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('verification_note');
            });
        }
    }
};

