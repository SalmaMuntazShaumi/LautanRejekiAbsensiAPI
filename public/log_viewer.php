<?php
// HAPUS FILE INI SETELAH SELESAI DEBUG!
$log = file_get_contents(__DIR__ . '/../../laravel_api/storage/logs/laravel.log');
$lines = array_slice(explode("\n", $log), -100);
echo '<pre>' . implode("\n", $lines) . '</pre>';