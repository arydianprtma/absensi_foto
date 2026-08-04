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
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->time('check_in_start')->default('06:00:00');
            $table->time('check_in_end')->default('07:30:00');
            $table->time('check_out_start')->default('15:00:00');
            $table->time('check_out_end')->default('17:00:00');
            $table->timestamps();
        });

        // Add check_out_time to attendances table if not exists
        if (! Schema::hasColumn('attendances', 'check_out_time')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->time('check_out_time')->nullable()->after('check_in_time');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');

        if (Schema::hasColumn('attendances', 'check_out_time')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('check_out_time');
            });
        }
    }
};
