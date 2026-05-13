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
        Schema::table('attendances', function (Blueprint $table) {

            // status
            $table->string('status')->nullable();

            // photo
            $table->longText('clock_in_photo')->nullable();
            $table->longText('clock_out_photo')->nullable();

            // optional
            $table->text('early_out_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->dropColumn([
                'status',
                'clock_in_photo',
                'clock_out_photo',
                'early_out_reason',
            ]);
        });
    }
};