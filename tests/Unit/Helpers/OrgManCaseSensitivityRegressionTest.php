<?php

declare(strict_types=1);

use OrgManagement\Helpers\DatastarSSE;
use OrgManagement\Services\Strategies\RosterManagementStrategy;

it('loads dependencies when the relative path casing differs from filesystem casing', function (): void {
    require_once dirname(__DIR__, 3) . '/src/OrgMan.php';

    \OrgManagement\orgman_require_once_compat('helpers/DatastarSSE.php');
    \OrgManagement\orgman_require_once_compat('services/strategies/rostermanagementstrategy.php');

    expect(class_exists(DatastarSSE::class))->toBeTrue();
    expect(interface_exists(RosterManagementStrategy::class))->toBeTrue();
});

it('throws a runtime exception for missing dependencies', function (): void {
    require_once dirname(__DIR__, 3) . '/src/OrgMan.php';

    expect(fn () => \OrgManagement\orgman_require_once_compat('helpers/DoesNotExist.php'))
        ->toThrow(RuntimeException::class, 'Missing required dependency');
});
