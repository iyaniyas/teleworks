<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->string('name')->index();
            $table->string('slug')->nullable()->unique();
            $table->string('domain')->nullable()->index();
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_suspended')->default(false);
            $table->timestamps();
        });

        // optional pivot for company users / recruiters
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('recruiter'); // owner, recruiter
            $table->timestamps();
        });

        // add nullable company_id to jobs
        Schema::table('jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            // Do not add FK yet - we'll backfill first
        });
    }

    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('companies');
    }
}

