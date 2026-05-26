<?php
require 'vendor/autoload.php';
$app = \Config\Services::codeigniter(new \Config\App());
$app->initialize();

$db = \Config\Database::connect();

echo "--- AIRLINES ---\n";
$airlines = $db->table('airlines')->get()->getResultArray();
print_r($airlines);

echo "\n--- LOOKUPS ---\n";
$lookups = $db->table('lookup_values')->get()->getResultArray();
print_r($lookups);

echo "\n--- TRANSPORTERS ---\n";
$trans = $db->table('transporters')->get()->getResultArray();
print_r($trans);

echo "\n--- DRIVERS ---\n";
$drivers = $db->table('drivers')->get()->getResultArray();
print_r($drivers);
