<?php
require 'vendor/autoload.php';
$app = \Config\Services::codeigniter(new \Config\App());
$app->initialize();

$db = \Config\Database::connect();
try {
    $db->query("ALTER TABLE lookup_values DROP COLUMN sort_order");
    echo "Column sort_order dropped successfully.\n";
} catch (\Exception $e) {
    echo "Error or already dropped: " . $e->getMessage() . "\n";
}
