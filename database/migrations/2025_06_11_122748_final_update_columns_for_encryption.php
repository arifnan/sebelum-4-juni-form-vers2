<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- STUDENTS TABLE ---
        // Drop unique index on email first (if exists)
        // Use raw SQL to be explicit and avoid builder issues
        $indexNameEmailStudents = 'students_email_unique'; // Default Laravel index name
        if ($this->hasIndex('students', $indexNameEmailStudents)) {
            DB::statement("ALTER TABLE `students` DROP INDEX `{$indexNameEmailStudents}`");
        }
        // Check if our custom index exists if you ran it partially before
        if ($this->hasIndex('students', 'students_email_unique_text_idx')) {
             DB::statement("ALTER TABLE `students` DROP INDEX `students_email_unique_text_idx`");
        }

        // Change column types using raw SQL (ensure nullable as needed)
        DB::statement("ALTER TABLE `students` MODIFY `name` TEXT NOT NULL");
        DB::statement("ALTER TABLE `students` MODIFY `email` TEXT NOT NULL"); // email is NOT NULL by default in Laravel
        DB::statement("ALTER TABLE `students` MODIFY `address` TEXT NULL"); // Check if address can be NULL in your original migration
        DB::statement("ALTER TABLE `students` MODIFY `grade` TEXT NOT NULL");
        DB::statement("ALTER TABLE `students` MODIFY `profile_photo_path` TEXT NULL"); // Check if profile_photo_path can be NULL

        // Add unique index on email with a prefix length (for TEXT columns)
        DB::statement("ALTER TABLE `students` ADD UNIQUE `students_email_unique_text_idx` (`email`(191))");


        // --- TEACHERS TABLE ---
        // Drop unique indexes on nip and email first (if exists)
        $indexNameNipTeachers = 'teachers_nip_unique';
        $indexNameEmailTeachers = 'teachers_email_unique';
        if ($this->hasIndex('teachers', $indexNameNipTeachers)) {
            DB::statement("ALTER TABLE `teachers` DROP INDEX `{$indexNameNipTeachers}`");
        }
        if ($this->hasIndex('teachers', $indexNameEmailTeachers)) {
            DB::statement("ALTER TABLE `teachers` DROP INDEX `{$indexNameEmailTeachers}`");
        }
        // Check if our custom indexes exist
        if ($this->hasIndex('teachers', 'teachers_nip_unique_text_idx')) {
             DB::statement("ALTER TABLE `teachers` DROP INDEX `teachers_nip_unique_text_idx`");
        }
        if ($this->hasIndex('teachers', 'teachers_email_unique_text_idx')) {
             DB::statement("ALTER TABLE `teachers` DROP INDEX `teachers_email_unique_text_idx`");
        }

        // Change column types using raw SQL (ensure nullable as needed)
        DB::statement("ALTER TABLE `teachers` MODIFY `nip` TEXT NOT NULL");
        DB::statement("ALTER TABLE `teachers` MODIFY `name` TEXT NOT NULL");
        DB::statement("ALTER TABLE `teachers` MODIFY `email` TEXT NOT NULL");
        DB::statement("ALTER TABLE `teachers` MODIFY `address` TEXT NULL");
        DB::statement("ALTER TABLE `teachers` MODIFY `subject` TEXT NOT NULL");
        DB::statement("ALTER TABLE `teachers` MODIFY `profile_photo_path` TEXT NULL");

        // Add unique index on nip and email with a prefix length
        DB::statement("ALTER TABLE `teachers` ADD UNIQUE `teachers_nip_unique_text_idx` (`nip`(191))");
        DB::statement("ALTER TABLE `teachers` ADD UNIQUE `teachers_email_unique_text_idx` (`email`(191))");


        // --- RESPONSES TABLE ---
        // Change column types using raw SQL (ensure nullable as needed)
        DB::statement("ALTER TABLE `responses` MODIFY `photo_path` TEXT NULL");
        DB::statement("ALTER TABLE `responses` MODIFY `latitude` TEXT NULL");
        DB::statement("ALTER TABLE `responses` MODIFY `longitude` TEXT NULL");


        // --- RESPONSE_ANSWERS TABLE ---
        // Change column types using raw SQL (ensure nullable as needed)
        DB::statement("ALTER TABLE `response_answers` MODIFY `answer_text` LONGTEXT NULL"); // Use LONGTEXT for very long answers

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse operations with raw SQL.
        // Be careful as converting TEXT back to VARCHAR might truncate data.

        // STUDENTS TABLE
        if ($this->hasIndex('students', 'students_email_unique_text_idx')) {
            DB::statement("ALTER TABLE `students` DROP INDEX `students_email_unique_text_idx`");
        }
        DB::statement("ALTER TABLE `students` MODIFY `name` VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE `students` MODIFY `email` VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE `students` ADD UNIQUE `students_email_unique` (`email`)"); // Re-add default index name
        DB::statement("ALTER TABLE `students` MODIFY `address` VARCHAR(255) NULL");
        DB::statement("ALTER TABLE `students` MODIFY `grade` VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE `students` MODIFY `profile_photo_path` VARCHAR(255) NULL");


        // TEACHERS TABLE
        if ($this->hasIndex('teachers', 'teachers_nip_unique_text_idx')) {
            DB::statement("ALTER TABLE `teachers` DROP INDEX `teachers_nip_unique_text_idx`");
        }
        if ($this->hasIndex('teachers', 'teachers_email_unique_text_idx')) {
            DB::statement("ALTER TABLE `teachers` DROP INDEX `teachers_email_unique_text_idx`");
        }
        DB::statement("ALTER TABLE `teachers` MODIFY `nip` VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE `teachers` ADD UNIQUE `teachers_nip_unique` (`nip`)");
        DB::statement("ALTER TABLE `teachers` MODIFY `name` VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE `teachers` MODIFY `email` VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE `teachers` ADD UNIQUE `teachers_email_unique` (`email`)");
        DB::statement("ALTER TABLE `teachers` MODIFY `address` VARCHAR(255) NULL");
        DB::statement("ALTER TABLE `teachers` MODIFY `subject` VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE `teachers` MODIFY `profile_photo_path` VARCHAR(255) NULL");


        // RESPONSES TABLE
        DB::statement("ALTER TABLE `responses` MODIFY `photo_path` VARCHAR(255) NULL");
        DB::statement("ALTER TABLE `responses` MODIFY `latitude` VARCHAR(255) NULL");
        DB::statement("ALTER TABLE `responses` MODIFY `longitude` VARCHAR(255) NULL");

        // RESPONSE_ANSWERS TABLE
        DB::statement("ALTER TABLE `response_answers` MODIFY `answer_text` VARCHAR(255) NULL");
    }

    /**
     * Helper to check if an index exists on a given table.
     * This uses a raw query to avoid Doctrine DBAL issues.
     */
    protected function hasIndex(string $tableName, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            try {
                $indexes = $connection->select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);
                return !empty($indexes);
            } catch (\Illuminate\Database\QueryException $e) {
                return false; // Table might not exist yet during initial migration
            }
        }
        return false;
    }
};