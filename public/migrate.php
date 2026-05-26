<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(__DIR__ . '/../');
require 'system/bootstrap.php';

$migrate = \Config\Services::migrations();

try {
    if ($migrate->latest()) {
        echo "Migrations run successfully.\n";
    } else {
        echo "No new migrations to run.\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
