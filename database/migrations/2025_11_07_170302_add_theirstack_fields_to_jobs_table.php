<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tambah discovered_at dulu (tidak merujuk kolom lain)
        Schema::table('jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs', 'discovered_at')) {
                $table->dateTime('discovered_at')->nullable()->after('source');
            }
        });

        // Lalu tambahkan posted_at dan easy_apply (discovered_at sekarang sudah ada)
        Schema::table('jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs', 'posted_at')) {
                $table->dateTime('posted_at')->nullable()->after('discovered_at');
            }

            if (! Schema::hasColumn('jobs', 'easy_apply')) {
                $table->boolean('easy_apply')->default(false)->after('apply_url');
            }
        });
    }

    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $cols = ['posted_at', 'discovered_at', 'easy_apply'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('jobs', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};

