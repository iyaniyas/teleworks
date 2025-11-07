<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUrlFieldsAndFingerprintToJobs extends Migration
{
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'fingerprint')) {
                $table->string('fingerprint', 64)->nullable()->after('id');
            }
            if (!Schema::hasColumn('jobs', 'apply_url')) {
                $table->string('apply_url')->nullable()->after('description');
            }
            if (!Schema::hasColumn('jobs', 'source_url')) {
                $table->string('source_url')->nullable()->after('apply_url');
            }
            if (!Schema::hasColumn('jobs', 'final_url')) {
                $table->string('final_url')->nullable()->after('source_url');
            }
            if (!Schema::hasColumn('jobs', 'url_source')) {
                $table->string('url_source')->nullable()->after('final_url');
            }
        });

        // Add unique index separately to avoid issues on existing duplicates
        // Run this only AFTER kamu membersihkan duplikat
    }

    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'url_source')) {
                $table->dropColumn('url_source');
            }
            if (Schema::hasColumn('jobs', 'final_url')) {
                $table->dropColumn('final_url');
            }
            if (Schema::hasColumn('jobs', 'source_url')) {
                $table->dropColumn('source_url');
            }
            if (Schema::hasColumn('jobs', 'apply_url')) {
                $table->dropColumn('apply_url');
            }
            if (Schema::hasColumn('jobs', 'fingerprint')) {
                $table->dropColumn('fingerprint');
            }
        });
    }
}

