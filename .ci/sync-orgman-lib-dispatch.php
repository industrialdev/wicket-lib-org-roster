<?php

declare(strict_types=1);

$root = dirname(__DIR__, 4);
$script = $root . '/vendor/industrialdev/wicket-lib-org-roster/.ci/sync-orgman-lib.php';

if (is_file($script) && is_readable($script)) {
    $command = escapeshellarg((string) PHP_BINARY) . ' ' . escapeshellarg($script);
    passthru($command, $exitCode);
    exit((int) $exitCode);
}

fwrite(STDERR, "[orgman-sync] Vendor sync script not found: {$script}\n");
exit(1);
