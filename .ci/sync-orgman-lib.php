<?php

declare(strict_types=1);

function wicket_orgman_sync_resolve_project_root(): string
{
    $cwd = getcwd();
    if (is_string($cwd) && is_file($cwd . '/composer.json')) {
        if (is_dir($cwd . '/web/app') || is_dir($cwd . '/wp-content')) {
            return $cwd;
        }
    }

    $cursor = __DIR__;
    for ($i = 0; $i < 8; $i++) {
        if (is_file($cursor . '/composer.json')) {
            if (is_dir($cursor . '/web/app') || is_dir($cursor . '/wp-content')) {
                return $cursor;
            }
        }

        $parent = dirname($cursor);
        if ($parent === $cursor) {
            break;
        }

        $cursor = $parent;
    }

    throw new RuntimeException('Unable to resolve WordPress project root for OrgMan sync.');
}

function wicket_orgman_sync_resolve_target(string $root): string
{
    $bedrock_target = $root . '/web/app/libs/wicket-lib-org-roster';
    if (is_dir($root . '/web/app')) {
        return $bedrock_target;
    }

    $standard_target = $root . '/wp-content/libs/wicket-lib-org-roster';
    if (is_dir($root . '/wp-content')) {
        return $standard_target;
    }

    throw new RuntimeException('Unable to resolve target libs directory for OrgMan sync.');
}

$root = wicket_orgman_sync_resolve_project_root();
$source = $root . '/vendor/industrialdev/wicket-lib-org-roster';
$target = wicket_orgman_sync_resolve_target($root);

if (!is_dir($source)) {
    fwrite(STDERR, "[orgman-sync] Source not found: {$source}\n");
    exit(1);
}

if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0775, true) && !is_dir(dirname($target))) {
    fwrite(STDERR, "[orgman-sync] Failed to create destination directory: " . dirname($target) . "\n");
    exit(1);
}

$removeTree = static function (string $path): void {
    if (!file_exists($path) && !is_link($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        if ($item->isLink() || $item->isFile()) {
            if (!unlink($item->getPathname())) {
                throw new RuntimeException("Failed to remove file: {$item->getPathname()}");
            }
        } elseif ($item->isDir()) {
            if (!rmdir($item->getPathname())) {
                throw new RuntimeException("Failed to remove directory: {$item->getPathname()}");
            }
        }
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

$swapTarget = static function (string $stagedPath, string $targetPath) use ($removeTree): void {
    // 1. Delete the old copy while the staged copy already exists under a temp name.
    //    The library is live until the rename in step 2 — minimum downtime window.
    if (file_exists($targetPath)) {
        $removeTree($targetPath);
    }

    // 2. Near-atomic: rename the fully-copied staging dir into the final path.
    if (!rename($stagedPath, $targetPath)) {
        throw new RuntimeException("Failed to move staged directory into place: {$stagedPath} -> {$targetPath}");
    }
};

try {
    $staging = dirname($target) . '/.wicket-lib-org-roster-sync-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
    $copyTree($source, $staging);
    $swapTarget($staging, $target);
    fwrite(STDOUT, "[orgman-sync] Synced wicket-lib-org-roster to {$target}\n");
    exit(0);
} catch (Throwable $e) {
    if (isset($staging) && file_exists($staging)) {
        try {
            $removeTree($staging);
        } catch (Throwable) {
            // best-effort cleanup; original error takes priority
        }
    }
    fwrite(STDERR, '[orgman-sync] ' . $e->getMessage() . "\n");
    exit(1);
}
