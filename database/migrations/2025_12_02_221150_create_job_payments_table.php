<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('job_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('package_id')->nullable()->index();
            $table->string('external_id')->nullable()->unique();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->string('payment_gateway')->nullable();
            $table->enum('status', ['pending','paid','failed','refunded'])->default('pending')->index();
            $table->string('transaction_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            // optional foreign keys: enable only if you are sure migrations order is correct
            // $table->foreign('job_id')->references('id')->on('jobs')->onDelete('set null');
            // $table->foreign('package_id')->references('id')->on('job_packages')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_payments');
    }
};

