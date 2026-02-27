<?php

declare(strict_types=1);

function wicket_orgman_sync_resolve_project_root(): string
{
    $cwd = getcwd();
    if (is_string($cwd) && is_file($cwd . '/composer.json') && is_dir($cwd . '/web/app')) {
        return $cwd;
    }

    $cursor = __DIR__;
    for ($i = 0; $i < 8; $i++) {
        if (is_file($cursor . '/composer.json') && is_dir($cursor . '/web/app')) {
            return $cursor;
        }

        $parent = dirname($cursor);
        if ($parent === $cursor) {
            break;
        }

        $cursor = $parent;
    }

    throw new RuntimeException('Unable to resolve Bedrock project root for OrgMan sync.');
}

$root = wicket_orgman_sync_resolve_project_root();
$source = $root . '/vendor/industrialdev/wicket-lib-org-roster';
$target = $root . '/web/app/libs/wicket-lib-org-roster';

if (!is_dir($source)) {
    fwrite(STDERR, "[orgman-sync] Source not found: {$source}\n");
    exit(1);
}

if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0775, true) && !is_dir(dirname($target))) {
    fwrite(STDERR, "[orgman-sync] Failed to create destination directory: " . dirname($target) . "\n");
    exit(1);
}

$removeTree = static function (string $path) use (&$removeTree): void {
    if (!file_exists($path)) {
        return;
    }

    if (is_link($path) || is_file($path)) {
        if (!unlink($path)) {
            throw new RuntimeException("Failed to remove file: {$path}");
        }

        return;
    }

    $items = scandir($path);
    if ($items === false) {
        throw new RuntimeException("Failed to scan directory: {$path}");
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $removeTree($path . DIRECTORY_SEPARATOR . $item);
    }

    if (!rmdir($path)) {
        throw new RuntimeException("Failed to remove directory: {$path}");
    }
};

$copyTree = static function (string $from, string $to) use (&$copyTree): void {
    if (is_link($from)) {
        $linkTarget = readlink($from);
        if ($linkTarget === false) {
            throw new RuntimeException("Failed to read link: {$from}");
        }
        if (!symlink($linkTarget, $to)) {
            throw new RuntimeException("Failed to create symlink: {$to}");
        }

        return;
    }

    if (is_file($from)) {
        if (!copy($from, $to)) {
            throw new RuntimeException("Failed to copy file: {$from} -> {$to}");
        }

        return;
    }

    if (!is_dir($to) && !mkdir($to, 0775, true) && !is_dir($to)) {
        throw new RuntimeException("Failed to create directory: {$to}");
    }

    $items = scandir($from);
    if ($items === false) {
        throw new RuntimeException("Failed to scan source directory: {$from}");
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $copyTree(
            $from . DIRECTORY_SEPARATOR . $item,
            $to . DIRECTORY_SEPARATOR . $item
        );
    }
};

try {
    $removeTree($target);
    $copyTree($source, $target);
    fwrite(STDOUT, "[orgman-sync] Synced wicket-lib-org-roster to {$target}\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, '[orgman-sync] ' . $e->getMessage() . "\n");
    exit(1);
}
