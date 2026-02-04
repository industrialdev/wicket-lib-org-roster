<?php

declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach(function (): void {
    Monkey\setUp();

    Functions\stubTranslationFunctions();
    Functions\stubEscapeFunctions();

    // Global WP shims are defined in tests/helpers/wp-shims.php to avoid Patchwork redefinition issues.
    if (function_exists('orgroster_test_reset_store')) {
        orgroster_test_reset_store();
    }
});

afterEach(function (): void {
    Monkey\tearDown();
});
