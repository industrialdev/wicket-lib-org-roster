<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use OrgManagement\OrgMan;

it('resolves base URI from ABSPATH when package is installed in root vendor directory', function (): void {
    $packagePath = rtrim((string) ABSPATH, '/') . '/vendor/industrialdev/wicket-lib-org-roster';

    $GLOBALS['__orgroster_filters']['wicket/acc/orgman/base_path'] = [
        static function ($value) use ($packagePath) {
            return $packagePath;
        },
    ];

    Functions\when('content_url')->alias(static function ($path = ''): string {
        return 'https://example.test/wp-content' . ($path !== '' ? '/' . ltrim((string) $path, '/') : '');
    });

    Functions\when('site_url')->alias(static function ($path = ''): string {
        return 'https://example.test' . ($path !== '' ? '/' . ltrim((string) $path, '/') : '');
    });

    Functions\when('trailingslashit')->alias(static fn (string $value): string => rtrim($value, '/') . '/');

    $class = new ReflectionClass(OrgMan::class);
    $orgman = $class->newInstanceWithoutConstructor();
    $getBaseUri = Closure::bind(
        function (): string {
            /* @var OrgMan $this */
            return $this->get_base_uri();
        },
        $orgman,
        OrgMan::class
    );

    expect($getBaseUri())
        ->toBe('https://example.test/vendor/industrialdev/wicket-lib-org-roster/');
});
