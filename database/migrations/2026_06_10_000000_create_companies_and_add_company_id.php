<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('companies')->insert([
            [
                'name' => 'Lautan Rejeki Pusat',
                'slug' => 'pusat',
                'location' => 'Lokasi utama',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Lautan Rejeki Cabang',
                'slug' => 'cabang',
                'location' => 'Lokasi cabang',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $defaultCompanyId = DB::table('companies')->where('slug', 'pusat')->value('id');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        DB::table('users')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['company_id', 'email']);
            $table->unique(['company_id', 'phone']);
        });

        foreach (['attendances', 'time_offs', 'driver_locations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->whereNull('attendances.company_id')
            ->update(['attendances.company_id' => DB::raw('users.company_id')]);

        DB::table('time_offs')
            ->join('users', 'time_offs.user_id', '=', 'users.id')
            ->whereNull('time_offs.company_id')
            ->update(['time_offs.company_id' => DB::raw('users.company_id')]);

        DB::table('driver_locations')
            ->join('users', 'driver_locations.user_id', '=', 'users.id')
            ->whereNull('driver_locations.company_id')
            ->update(['driver_locations.company_id' => DB::raw('users.company_id')]);
    }

    public function down(): void
    {
        foreach (['driver_locations', 'time_offs', 'attendances'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'phone']);
            $table->dropUnique(['company_id', 'email']);
            $table->unique('email');
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::dropIfExists('companies');
    }
};
