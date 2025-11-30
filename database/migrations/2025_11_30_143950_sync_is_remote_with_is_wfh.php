<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // pastikan kolom is_remote ada
        if (! Schema::hasColumn('jobs', 'is_remote')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->boolean('is_remote')->default(false)->after('type');
            });
        }

        // sinkronisasi aman: hanya jalankan update jika kolom sumber ada
        if (Schema::hasColumn('jobs', 'is_wfh')) {
            DB::table('jobs')->where('is_wfh', 1)->update(['is_remote' => 1]);
        }

        if (Schema::hasColumn('jobs', 'remote')) {
            DB::table('jobs')->where('remote', 1)->update(['is_remote' => 1]);
        }

        if (Schema::hasColumn('jobs', 'type')) {
            DB::table('jobs')
                ->whereRaw("LOWER(COALESCE(type,'')) LIKE ?", ['%remote%'])
                ->orWhereRaw("LOWER(COALESCE(type,'')) LIKE ?", ['%wfh%'])
                ->update(['is_remote' => 1]);
        }
    }

    public function down()
    {
        // tidak mengembalikan perubahan nilai is_remote karena destructive
    }
};

