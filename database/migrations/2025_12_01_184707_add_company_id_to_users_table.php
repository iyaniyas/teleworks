<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // nullable agar tidak memaksa update semua user segera
            $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();

            // jika ada tabel companies.id sebagai primary key unsignedBigInteger
            // uncomment line berikut jika kamu ingin FK constraint (pastikan companies table sudah ada)
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('SET NULL');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // uncomment if you created foreign key
            // $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};

