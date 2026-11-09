<?php
// Dev-only CLI: package a Moodle plugin folder into a clean, forward-slash ZIP
// suitable for Moodle's "Install plugin from ZIP file" web installer.
//
// Usage: php makezip.php <source-plugin-dir> <zip-root-folder-name> <output.zip>
//   e.g. php makezip.php /bitnami/moodle/mod/optiksinav optiksinav /tmp/mod_optiksinav.zip

$src = rtrim($argv[1] ?? '', '/');
$rootname = $argv[2] ?? '';
$out = $argv[3] ?? '';

if ($src === '' || $rootname === '' || $out === '' || !is_dir($src)) {
    fwrite(STDERR, "Usage: php makezip.php <src-dir> <root-name> <output.zip>\n");
    exit(1);
}

@unlink($out);
$zip = new ZipArchive();
if ($zip->open($out, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Cannot open $out for writing\n");
    exit(1);
}

// Üretim paketine girmemesi gerekenler.
$excludes = ['/.git/', '/.github/', '/node_modules/'];

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$files = 0;
foreach ($it as $file) {
    $path = $file->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($src) + 1));

    $skip = false;
    foreach ($excludes as $ex) {
        if (strpos('/' . $rel . '/', $ex) !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }

    $zippath = $rootname . '/' . $rel;          // Daima forward-slash.
    if ($file->isDir()) {
        $zip->addEmptyDir($zippath);
    } else {
        $zip->addFile($path, $zippath);
        $files++;
    }
}

$zip->close();
echo "OK $out (files=$files)\n";
