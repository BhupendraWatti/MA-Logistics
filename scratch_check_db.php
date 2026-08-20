<?php
define('FCPATH', __DIR__ . '/public/');
require __DIR__ . '/vendor/autoload.php';

$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$fields = $db->getFieldNames('companies');
echo "Companies table columns: " . implode(', ', $fields) . "\n";

if (!in_array('logo_path', $fields)) {
    echo "Adding logo_path column...\n";
    $db->query("ALTER TABLE companies ADD COLUMN logo_path VARCHAR(255) NULL AFTER signature_image");
}
if (!in_array('logo_image', $fields)) {
    echo "Adding logo_image column...\n";
    $db->query("ALTER TABLE companies ADD COLUMN logo_image VARCHAR(255) NULL AFTER logo_path");
}
echo "DB check done!\n";
