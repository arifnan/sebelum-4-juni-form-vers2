<?php

// Menentukan path absolut ke direktori root Laravel Anda
// Berdasarkan logcat sebelumnya, path-nya adalah sebagai berikut:
$projectPath = '/home/ilta-services-e-form/htdocs/e-form.ilta-services.tech';

// Path ke file artisan
$artisanPath = $projectPath . '/artisan';

// Cek apakah file artisan ada
if (!file_exists($artisanPath)) {
    die("Error: File 'artisan' tidak ditemukan di path yang ditentukan. Periksa kembali variabel \$projectPath.");
}

// Menjalankan perintah 'storage:link'
// '2>&1' digunakan untuk menangkap output error jika ada
$command = 'php ' . escapeshellarg($artisanPath) . ' storage:link 2>&1';
$output = shell_exec($command);

// Tampilkan hasilnya ke browser
echo "<pre>Menjalankan perintah: " . htmlspecialchars($command) . "</pre>";
echo "<pre>Hasil:\n" . htmlspecialchars($output) . "</pre>";

?>