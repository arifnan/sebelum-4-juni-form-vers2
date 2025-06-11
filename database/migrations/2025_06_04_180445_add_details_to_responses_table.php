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
        Schema::table('responses', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('student_id'); // Untuk menyimpan path relatif foto
            $table->decimal('latitude', 10, 8)->nullable()->after('photo_path');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->boolean('is_location_valid')->default(false)->after('longitude');
            $table->timestamp('submitted_at')->nullable()->default(now())->after('is_location_valid'); // Kolom waktu submit
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'latitude', 'longitude', 'is_location_valid', 'submitted_at']);
        });
    }
};