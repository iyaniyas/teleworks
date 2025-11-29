<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobApplicationsTable extends Migration
{
    public function up()
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('resume_path')->nullable();
            $table->text('cover_letter')->nullable();
            $table->enum('status', ['applied','viewed','shortlisted','interview','rejected','hired'])->default('applied');
            $table->timestamps();

            $table->unique(['job_id','user_id']); // prevent duplicate applications
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_applications');
    }
}

