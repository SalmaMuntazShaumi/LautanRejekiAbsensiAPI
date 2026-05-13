<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            if (!Schema::hasColumn('attendances', 'status')) {

                $table->string('status')
                    ->default('present');

            }

        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->dropColumn('status');

        });
    }
};
