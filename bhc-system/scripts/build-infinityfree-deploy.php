<?php
/**
 * Build InfinityFree upload package into deploy/infinityfree/package/
 * Does not modify your local XAMPP project files (except you run it manually).
 *
 * Usage: php scripts/build-infinityfree-deploy.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$out = $root . DIRECTORY_SEPARATOR . 'deploy' . DIRECTORY_SEPARATOR . 'infinityfree' . DIRECTORY_SEPARATOR . 'package';

$copyDirs = ['config', 'core', 'controllers', 'models', 'views'];
$publicDir = $root . DIRECTORY_SEPARATOR . 'public';

function deleteDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

function copyDir(string $src, string $dest, array $excludeFiles = []): void
{
    if (!is_dir($src)) {
        throw new RuntimeException("Missing folder: {$src}");
    }
    if (!is_dir($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
        throw new RuntimeException("Cannot create: {$dest}");
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $rel = substr($item->getPathname(), strlen($src) + 1);
        $base = basename($rel);
        if ($item->isFile() && in_array($base, $excludeFiles, true)) {
            continue;
        }
        $target = $dest . DIRECTORY_SEPARATOR . $rel;
        if ($item->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                throw new RuntimeException("Cannot create: {$target}");
            }
        } else {
            $parent = dirname($target);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new RuntimeException("Cannot create: {$parent}");
            }
            if (!copy($item->getPathname(), $target)) {
                throw new RuntimeException("Copy failed: {$item->getPathname()}");
            }
        }
    }
}

echo "BHC InfinityFree deploy builder\n";
echo "Output: {$out}\n\n";

if (is_dir($out)) {
    echo "Cleaning previous package...\n";
    deleteDir($out);
}
mkdir($out, 0755, true);

echo "Copying public/ (index, .htaccess, assets)...\n";
foreach (scandir($publicDir) ?: [] as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }
    $src = $publicDir . DIRECTORY_SEPARATOR . $entry;
    $dest = $out . DIRECTORY_SEPARATOR . $entry;
    if (is_dir($src)) {
        copyDir($src, $dest);
    } else {
        copy($src, $dest);
    }
}

$configExclude = [
    'app.php',
    'app.local.php',
    'app.infinityfree.php',
    'database.php',
    'database.infinityfree.example.php',
    'database.infinityfree.php',
];

echo "Copying application folders...\n";
foreach ($copyDirs as $dir) {
    copyDir(
        $root . DIRECTORY_SEPARATOR . $dir,
        $out . DIRECTORY_SEPARATOR . $dir,
        $dir === 'config' ? $configExclude : []
    );
}

$dbDeploy = $root . '/config/database.infinityfree.php';
$dbExample = $root . '/config/database.infinityfree.example.php';
$dbTarget = $out . '/config/database.php';
if (is_file($dbDeploy)) {
    copy($dbDeploy, $dbTarget);
    echo "Using config/database.infinityfree.php for deploy database.php\n";
} elseif (is_file($dbExample)) {
    copy($dbExample, $dbTarget);
    echo "Using config/database.infinityfree.example.php (edit MySQL credentials before upload)\n";
} else {
    throw new RuntimeException('Missing config/database.infinityfree.example.php');
}

copy($root . '/config/app.infinityfree.php', $out . '/config/app.php');
echo "Using config/app.infinityfree.php for deploy app.php\n";

$readme = <<<TXT
Upload everything in this folder into InfinityFree htdocs/

Site: https://bhcs.free.nf

Built: %s
TXT;

file_put_contents(
    $out . '/UPLOAD_README.txt',
    sprintf($readme, date('Y-m-d H:i:s'))
);

echo "\nDone. Zip this folder or upload all contents to htdocs on InfinityFree.\n";
echo "Local XAMPP project at {$root} was not changed.\n";
