<?php

declare(strict_types=1);

$target = $argv[1] ?? 'src';
$root = realpath(__DIR__ . '/../' . ltrim($target, '/'));

if ($root === false || !is_dir($root)) {
    fwrite(STDERR, "[case-check] Target directory not found: {$target}\n");
    exit(2);
}

$root = rtrim(str_replace('\\', '/', $root), '/');

$paths_by_normalized = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $item) {
    /** @var SplFileInfo $item */
    $full_path = str_replace('\\', '/', $item->getPathname());
    $relative_path = substr($full_path, strlen($root) + 1);
    if ($relative_path === false || $relative_path === '') {
        continue;
    }

    $normalized = strtolower($relative_path);
    if (!isset($paths_by_normalized[$normalized])) {
        $paths_by_normalized[$normalized] = [];
    }

    $paths_by_normalized[$normalized][] = $relative_path;
}

$collisions = [];
foreach ($paths_by_normalized as $variants) {
    $unique = array_values(array_unique($variants));
    if (count($unique) > 1) {
        sort($unique, SORT_STRING);
        $collisions[] = $unique;
    }
}

if (count($collisions) === 0) {
    fwrite(STDOUT, "[case-check] OK: no case-colliding paths found under {$target}\n");
    exit(0);
}

fwrite(STDERR, "[case-check] ERROR: case-colliding paths detected under {$target}:\n");
foreach ($collisions as $group) {
    fwrite(STDERR, '  - ' . implode('  <->  ', $group) . "\n");
}

exit(1);
