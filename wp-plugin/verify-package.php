<?php

declare(strict_types=1);

$sourceDir = __DIR__ . '/ma-logistics-tracking';
$archivePath = __DIR__ . '/ma-logistics-tracking.zip';
$archiveRoot = 'ma-logistics-tracking/';

if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, "PHP zip extension is required.\n");
    exit(2);
}

$zip = new ZipArchive();
if ($zip->open($archivePath) !== true) {
    fwrite(STDERR, "Cannot open {$archivePath}.\n");
    exit(2);
}

$expected = [];
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($files as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDir) + 1));
    $expected[$archiveRoot . $relativePath] = hash_file('sha256', $file->getPathname());
}

$actual = [];
for ($index = 0; $index < $zip->numFiles; $index++) {
    $name = $zip->getNameIndex($index);
    if ($name === false || str_ends_with($name, '/')) {
        continue;
    }

    $contents = $zip->getFromIndex($index);
    $actual[$name] = $contents === false ? null : hash('sha256', $contents);
}
$zip->close();

ksort($expected);
ksort($actual);

if ($actual !== $expected) {
    foreach (array_unique(array_merge(array_keys($expected), array_keys($actual))) as $name) {
        if (($expected[$name] ?? null) !== ($actual[$name] ?? null)) {
            fwrite(STDERR, "Package mismatch: {$name}\n");
        }
    }
    exit(1);
}

echo "Plugin package matches source (" . count($expected) . " files).\n";
