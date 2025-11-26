<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameSearchlogColumns extends Migration
{
    public function up()
    {
        Schema::table('search_logs', function (Blueprint $table) {
            // rename query -> q
            if (Schema::hasColumn('search_logs', 'query')) {
                $table->renameColumn('query', 'q');
            }

            // optional: rename filters -> params
            if (Schema::hasColumn('search_logs', 'filters')) {
                $table->renameColumn('filters', 'params');
            }

            // optional: rename results_count -> result_count
            if (Schema::hasColumn('search_logs', 'results_count')) {
                $table->renameColumn('results_count', 'result_count');
            }

            // optional: rename ip -> user_ip
            if (Schema::hasColumn('search_logs', 'ip')) {
                $table->renameColumn('ip', 'user_ip');
            }
        });
    }

    public function down()
    {
        Schema::table('search_logs', function (Blueprint $table) {
            if (Schema::hasColumn('search_logs', 'q')) {
                $table->renameColumn('q', 'query');
            }
            if (Schema::hasColumn('search_logs', 'params')) {
                $table->renameColumn('params', 'filters');
            }
            if (Schema::hasColumn('search_logs', 'result_count')) {
                $table->renameColumn('result_count', 'results_count');
            }
            if (Schema::hasColumn('search_logs', 'user_ip')) {
                $table->renameColumn('user_ip', 'ip');
            }
        });
    }
}

