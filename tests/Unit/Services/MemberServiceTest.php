<?php

declare(strict_types=1);

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\MemberService;

it('matches any role for a person when all_true is false', function (): void {
    $service = new MemberService(new ConfigService());

    $permissionStub = new class extends OrgManagement\Services\PermissionService {
        public function get_person_current_roles_by_org_id($personUuid, $orgId): array
        {
            return ['membership_owner', 'org_editor'];
        }
    };

    (function ($stub): void {
        $this->permissionService = $stub;
    })->call($service, $permissionStub);

    expect($service->person_has_org_roles('person-1', 'membership_owner,missing_role', 'org-1'))
        ->toBeTrue();
});

it('requires all roles when all_true is true', function (): void {
    $service = new MemberService(new ConfigService());

    $permissionStub = new class extends OrgManagement\Services\PermissionService {
        public function get_person_current_roles_by_org_id($personUuid, $orgId): array
        {
            return ['membership_owner'];
        }
    };

    (function ($stub): void {
        $this->permissionService = $stub;
    })->call($service, $permissionStub);

    expect($service->person_has_org_roles('person-1', ['membership_owner', 'org_editor'], 'org-1', true))
        ->toBeFalse();
});

it('clears member cache transients for common sizes', function (): void {
    $service = new MemberService(new ConfigService());
    $membershipUuid = 'mem-1';

    $key = 'orgman_members_initial_' . md5($membershipUuid . '_1_10');
    set_transient($key, ['cached'], 300);

    $service->clear_members_cache($membershipUuid);

    expect(get_transient($key))->toBeFalse();
});
