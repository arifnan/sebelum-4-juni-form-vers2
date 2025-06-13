<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Teacher; // Tambahkan ini
use App\Models\Student; // Tambahkan ini
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class DecryptLoginColumns extends Command
{
    protected $signature = 'data:decrypt-login-columns';
    protected $description = 'Decrypts email and NIP columns to plaintext for login functionality.';

    public function handle()
    {
        $this->info('Starting decryption of login-related columns...');

        // --- PROSES TABEL TEACHERS ---
        $this->info('Memproses tabel Teachers...');
        $rawTeachers = DB::table('teachers')->get();
        $barTeachers = $this->output->createProgressBar(count($rawTeachers));
        $barTeachers->start();

        foreach ($rawTeachers as $rawTeacher) {
            $updateData = [];
            $recordChanged = false;

            // Mendekripsi Email Guru
            $originalEmail = property_exists($rawTeacher, 'email') ? $rawTeacher->email : null;
            if (!is_null($originalEmail) && trim($originalEmail) !== '') {
                try {
                    $decryptedEmail = Crypt::decryptString($originalEmail);
                    // Hanya update jika nilai dekripsi berbeda dari nilai mentah asli (artinya memang terenkripsi)
                    // atau jika nilai dekripsi itu sendiri valid dan berbeda dari string terenkripsi
                    if ($decryptedEmail !== $originalEmail) {
                        $updateData['email'] = $decryptedEmail;
                        $recordChanged = true;
                    }
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    // Jika dekripsi gagal, kemungkinan itu memang plaintext dari awal atau rusak.
                    // Dalam kasus ini, kita biarkan saja nilai aslinya (asumsi itu plaintext).
                    Log::warning("Gagal mendekripsi email guru untuk ID {$rawTeacher->id}. Mungkin sudah plaintext atau rusak. Error: " . $e->getMessage());
                    // Tidak perlu update, karena nilai sudah dianggap plaintext atau rusak dan tidak bisa didekripsi
                } catch (\Throwable $e) {
                     $this->error("Error tak terduga saat mendekripsi email guru ID {$rawTeacher->id}: " . $e->getMessage());
                     // Lanjutkan, tetapi log error
                }
            } else { // Jika nilai original null atau kosong
                if ($rawTeacher->email !== null) { // Jika di DB tidak null, tapi seharusnya null/kosong
                    $updateData['email'] = null; // Set ke null
                    $recordChanged = true;
                }
            }


            // Mendekripsi NIP Guru
            $originalNip = property_exists($rawTeacher, 'nip') ? $rawTeacher->nip : null;
            if (!is_null($originalNip) && trim($originalNip) !== '') {
                try {
                    $decryptedNip = Crypt::decryptString($originalNip);
                    if ($decryptedNip !== $originalNip) {
                        $updateData['nip'] = $decryptedNip;
                        $recordChanged = true;
                    }
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    Log::warning("Gagal mendekripsi NIP guru untuk ID {$rawTeacher->id}. Mungkin sudah plaintext atau rusak. Error: " . $e->getMessage());
                } catch (\Throwable $e) {
                     $this->error("Error tak terduga saat mendekripsi NIP guru ID {$rawTeacher->id}: " . $e->getMessage());
                }
            } else {
                if ($rawTeacher->nip !== null) {
                    $updateData['nip'] = null;
                    $recordChanged = true;
                }
            }

            if ($recordChanged && !empty($updateData)) {
                DB::table('teachers')->where('id', $rawTeacher->id)->update($updateData);
            }
            $barTeachers->advance();
        }
        $barTeachers->finish();
        $this->info("\nDekripsi tabel Teachers selesai.");

        // --- PROSES TABEL STUDENTS ---
        $this->info('Memproses tabel Students...');
        $rawStudents = DB::table('students')->get();
        $barStudents = $this->output->createProgressBar(count($rawStudents));
        $barStudents->start();

        foreach ($rawStudents as $rawStudent) {
            $updateData = [];
            $recordChanged = false;

            // Mendekripsi Email Siswa
            $originalEmail = property_exists($rawStudent, 'email') ? $rawStudent->email : null;
            if (!is_null($originalEmail) && trim($originalEmail) !== '') {
                try {
                    $decryptedEmail = Crypt::decryptString($originalEmail);
                    if ($decryptedEmail !== $originalEmail) {
                        $updateData['email'] = $decryptedEmail;
                        $recordChanged = true;
                    }
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    Log::warning("Gagal mendekripsi email siswa untuk ID {$rawStudent->id}. Mungkin sudah plaintext atau rusak. Error: " . $e->getMessage());
                } catch (\Throwable $e) {
                     $this->error("Error tak terduga saat mendekripsi email siswa ID {$rawStudent->id}: " . $e->getMessage());
                }
            } else {
                if ($rawStudent->email !== null) {
                    $updateData['email'] = null;
                    $recordChanged = true;
                }
            }

            if ($recordChanged && !empty($updateData)) {
                DB::table('students')->where('id', $rawStudent->id)->update($updateData);
            }
            $barStudents->advance();
        }
        $barStudents->finish();
        $this->info("\nDekripsi tabel Students selesai.");

        $this->info('Semua kolom yang ditentukan telah didekripsi.');
    }
}