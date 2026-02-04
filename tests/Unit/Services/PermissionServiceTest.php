<?php

declare(strict_types=1);

use OrgManagement\Services\PermissionService;

it('skips protected roles when removing permissions', function (): void {
    $service = new PermissionService();

    $result = $service->remove_person_roles_from_org('person-1', ['administrator'], 'org-1');

    expect($result)->toBeTrue();
});
