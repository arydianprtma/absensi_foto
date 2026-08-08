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
        Schema::table('students', function (Blueprint $table) {
            $table->string('rfid_uid')->nullable()->unique()->after('nisn');
            $table->string('nis')->nullable()->after('rfid_uid');
            $table->text('address')->nullable()->after('class_name');
            $table->string('school_origin')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['rfid_uid', 'nis', 'address', 'school_origin']);
        });
    }
};
