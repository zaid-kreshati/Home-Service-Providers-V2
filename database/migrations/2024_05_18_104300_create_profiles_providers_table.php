<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('image_id')->references('id')->on('images')->onDelete('cascade');
            $table->foreignId('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->integer('years_experience');
            $table->text('description');
            $table->integer('phone');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profileProvider');
    }
};
