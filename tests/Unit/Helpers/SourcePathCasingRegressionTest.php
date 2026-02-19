<?php

declare(strict_types=1);

it('keeps canonical src directory casing', function (): void {
    $root = dirname(__DIR__, 3) . '/src';

    expect(is_dir($root . '/Helpers'))->toBeTrue();
    expect(is_dir($root . '/Services'))->toBeTrue();
    expect(is_dir($root . '/Controllers'))->toBeTrue();
    expect(is_dir($root . '/Services/Strategies'))->toBeTrue();
});

it('has no case-only path collisions under src', function (): void {
    $root = dirname(__DIR__, 3) . '/src';
    $paths_by_normalized = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $item) {
        /** @var SplFileInfo $item */
        $relative_path = substr($item->getPathname(), strlen($root) + 1);
        $normalized_path = strtolower(str_replace('\\', '/', $relative_path));

        if (!isset($paths_by_normalized[$normalized_path])) {
            $paths_by_normalized[$normalized_path] = [];
        }

        $paths_by_normalized[$normalized_path][] = str_replace('\\', '/', $relative_path);
    }

    foreach ($paths_by_normalized as $candidates) {
        expect(array_unique($candidates))->toHaveCount(1);
    }
});
