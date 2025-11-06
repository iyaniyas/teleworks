<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->index();
            $table->text('description');
            $table->string('company')->nullable()->index();
            $table->string('location')->nullable()->index();
            $table->string('type')->nullable(); // WFH/hybrid/onsite
            $table->boolean('is_wfh')->default(false)->index();
            $table->string('search')->nullable()->index();
            $table->string('source_url')->nullable();
            $table->text('raw_html')->nullable();
            $table->boolean('is_imported')->default(false);
            $table->enum('status',['draft','published','expired','archived'])->default('published')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE jobs ADD FULLTEXT fulltext_idx (title, description, search)');
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};

