<?php

declare(strict_types=1);

use OrgManagement\Services\PermissionService;

it('skips protected roles when removing permissions', function (): void {
    $service = new PermissionService();

    $result = $service->removePersonRolesFromOrg('person-1', ['administrator'], 'org-1');

    expect($result)->toBeTrue();
});
