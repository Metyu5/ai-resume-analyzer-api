<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->text('job_description')->nullable();
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('keywords_matched')->default(0);
            $table->unsignedSmallInteger('keywords_total')->default(0);
            $table->text('summary')->nullable();
            $table->json('findings');
            $table->json('missing_keywords')->nullable();
            $table->json('suggestions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
