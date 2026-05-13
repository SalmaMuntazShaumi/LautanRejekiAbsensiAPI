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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();

            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_long', 10, 7)->nullable();

            $table->decimal('clock_out_lat', 10, 7)->nullable();
            $table->decimal('clock_out_long', 10, 7)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
