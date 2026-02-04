<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use OrgManagement\Helpers\TemplateHelper;

it('blocks template path traversal attempts', function (): void {
    Functions\when('wc_get_logger')->alias(fn() => new class {
        public function error(string $message, array $context = []): void {}
    });

    expect(fn() => TemplateHelper::wicket_orgman_get_template('../evil'))
        ->toThrow(RuntimeException::class);
});
