<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\ResponseExportController;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
// Route utama yang langsung menampilkan halaman login
Route::get('/', [AuthController::class, 'showLogin'])->name('home');


Route::get('/reset-specific-teacher-password', function () {
    $emailToReset = 'admin008@gmail.com'; // Email guru yang ingin di-reset
    $newPassword = 'passwordbaru123'; // <--- GANTI DENGAN PASSWORD BARU YANG SANGAT MUDAH DIINGAT UNTUK TESTING!

    $teacher = null;
    $allTeachers = Teacher::all();
    foreach ($allTeachers as $t) {
        try {
            // Jika Anda ingin memastikan perbandingan case-insensitive untuk email:
            // if (isset($t->email) && strtolower($t->email) === strtolower($emailToReset)) {
            if (isset($t->email) && $t->email === $emailToReset) {
                $teacher = $t;
                break;
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::error("Dekripsi email gagal saat reset password untuk user ID {$t->id}: " . $e->getMessage());
            continue;
        } catch (\Throwable $e) {
            Log::error("Error tak terduga saat reset password untuk user ID {$t->id}: " . $e->getMessage());
            continue;
        }
    }

    if ($teacher) {
        $teacher->password = Hash::make($newPassword);
        if ($teacher instanceof \Illuminate\Database\Eloquent\Model) { // Pastikan ini model Eloquent
            $teacher->save();
        }
        return "Password untuk guru dengan email: {$emailToReset} berhasil diatur ulang menjadi: {$newPassword}. ID Guru: {$teacher->id}";
    } else {
        return "Guru dengan email {$emailToReset} tidak ditemukan di database Anda.";
    }
});

// Route debugging login check
Route::get('/debug-login-check', function () {
    $testEmail = 'admin008@gmail.com'; // Email yang diuji
    $testPassword = 'admin008'; // Password yang diuji
    $testRole = 'teacher'; // Role yang diuji

    echo "<h2>Debugging Login Check</h2>";
    echo "Mencoba cek kredensial: Email = <b>{$testEmail}</b>, Password = <b>{$testPassword}</b>, Role = <b>{$testRole}</b><br><br>";

    $users = collect();
    if ($testRole === 'teacher') {
        $users = Teacher::all();
        echo "Memuat " . count($users) . " guru dari database.<br><br>";
    } elseif ($testRole === 'student') {
        $users = Student::all();
        echo "Memuat " . count($users) . " siswa dari database.<br><br>";
    } else {
        return "Role tidak valid.";
    }

    $foundUser = null;

    foreach ($users as $user) {
        echo "Memproses User ID: " . $user->id . "<br>";
        $userEmailDecrypted = 'DECRYPT_FAILED';
        $userNipDecrypted = 'DECRYPT_FAILED';

        try {
            $userEmailDecrypted = $user->email;
            echo " - Email user (didekripsi): " . $userEmailDecrypted . "<br>";
            if ($testRole === 'teacher' && property_exists($user, 'nip')) {
                $userNipDecrypted = $user->nip;
                echo " - NIP user (didekripsi): " . $userNipDecrypted . "<br>";
            }

            // Perbandingan Email
            if ($userEmailDecrypted === $testEmail) {
                echo " => COCOK: Email ditemukan!<br>";
                $foundUser = $user;
                break;
            }
            // Perbandingan NIP (jika input adalah NIP dan role teacher)
            if ($testRole === 'teacher' && property_exists($user, 'nip') && $userNipDecrypted === $testEmail) {
                echo " => COCOK: NIP ditemukan!<br>";
                $foundUser = $user;
                break;
            }

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            echo " - GAGAL DEKRIPSI untuk user ID " . $user->id . ": " . $e->getMessage() . "<br>";
            Log::error("Dekripsi gagal di debug-login-check untuk user ID {$user->id}: " . $e->getMessage());
            continue;
        } catch (\Throwable $e) {
            echo " - ERROR TAK TERDUGA untuk user ID " . $user->id . ": " . $e->getMessage() . "<br>";
            Log::error("Error tak terduga di debug-login-check untuk user ID {$user->id}: " . $e->getMessage());
            continue;
        }
        echo "<br>";
    }

    if ($foundUser) {
        echo "<h3>User Ditemukan di Database!</h3>";
        echo "ID: " . $foundUser->id . "<br>";
        echo "Nama: " . $foundUser->name . "<br>";
        echo "Email Didekripsi: " . $foundUser->email . "<br>";
        if (property_exists($foundUser, 'nip')) {
            echo "NIP Didekripsi: " . $foundUser->nip . "<br>";
        }

        echo "<br><h3>Verifikasi Password:</h3>";
        if (Hash::check($testPassword, $foundUser->password)) {
            echo "<b>Password COCOK!</b> Login seharusnya berhasil.<br>";
        } else {
            echo "<b>Password TIDAK COCOK!</b> Periksa kembali password plaintext dan hash di database.<br>";
        }
    } else {
        echo "<h3>User TIDAK DITEMUKAN di Database dengan kredensial yang diberikan.</h3>";
        echo "Pastikan email/NIP dan role sudah benar dan akun ada.<br>";
    }

    return "--- Selesai Debugging ---";
});


// **AUTH ROUTES**
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/admins', [AdminController::class, 'index']); // Menampilkan daftar admin
// **PROTECTED ROUTES (Hanya bisa diakses jika sudah login)**
  // **Dashboard**
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // **CRUD Admin**
    Route::resource('admin', AdminController::class)->except(['show']);

    // **CRUD Formulir**
    Route::resource('forms', FormController::class);

    // **CRUD Pertanyaan dalam Formulir**
    Route::resource('questions', QuestionController::class);

    // **Lihat Jawaban User**
    Route::resource('responses', ResponseController::class);

    Route::resource('teachers', TeacherController::class);

    Route::resource('students', StudentController::class);

Route::middleware(['auth:admin'])->group(function () {

});
Route::get('/export-responses/pdf', [ResponseExportController::class, 'exportPdf'])->name('responses.export.pdf');
Route::get('/export-responses/excel', [ResponseExportController::class, 'exportExcel'])->name('responses.export.excel');

