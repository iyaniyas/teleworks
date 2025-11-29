<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookmarksTable extends Migration
{
    public function up()
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('job_id')->index();
            $table->timestamps();

            // optional foreign key: jobs may exist in another DB; keep simple index
            $table->unique(['user_id','job_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookmarks');
    }
}

