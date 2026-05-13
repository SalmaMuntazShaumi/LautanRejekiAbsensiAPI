<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_offs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('type');

            $table->date('start_date');
            $table->date('end_date');

            $table->text('reason');

            $table->string('status')
                ->default('Menunggu Konfirmasi');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_offs');
    }
};
