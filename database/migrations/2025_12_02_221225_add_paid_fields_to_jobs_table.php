<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('status')->index();
            }
            if (!Schema::hasColumn('jobs', 'paid_until')) {
                $table->timestamp('paid_until')->nullable()->after('is_paid')->index();
            }
            if (!Schema::hasColumn('jobs', 'package_id')) {
                $table->unsignedBigInteger('package_id')->nullable()->after('paid_until')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'package_id')) {
                $table->dropColumn('package_id');
            }
            if (Schema::hasColumn('jobs', 'paid_until')) {
                $table->dropColumn('paid_until');
            }
            if (Schema::hasColumn('jobs', 'is_paid')) {
                $table->dropColumn('is_paid');
            }
        });
    }
};

