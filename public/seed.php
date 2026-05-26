<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(__DIR__ . '/../');
require 'system/bootstrap.php';

$seeder = \Config\Database::seeder();

try {
    $seeder->call('DummyDataSeeder');
    echo "Seeders run successfully.\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
