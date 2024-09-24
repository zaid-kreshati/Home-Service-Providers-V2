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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('provider_id')->references('id')->on('users')->onDelete('cascade');
            $table->date('date'); // Date column with day and month
            $table->time('hours'); // Time column for hours
            $table->enum('status', ['approved', 'pending', 'rejected', 'finished' , 'canceled'])->default('pending');
            $table->text('description')->nullable(); // Text column for description
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
