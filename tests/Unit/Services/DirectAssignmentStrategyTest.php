<?php

declare(strict_types=1);

use OrgManagement\Services\MembershipService;
use OrgManagement\Services\Strategies\DirectAssignmentStrategy;

it('resolves context membership uuid when org matches', function (): void {
    $strategy = new DirectAssignmentStrategy();

    $membershipStub = new class extends MembershipService {
        public function getOrgMembershipData(string $membershipUuid): ?array
        {
            return [
                'data' => [
                    'relationships' => [
                        'organization' => [
                            'data' => ['id' => 'org-1'],
                        ],
                    ],
                ],
            ];
        }
    };

    (function ($stub): void {
        $this->membershipService = $stub;
    })->call($strategy, $membershipStub);

    $result = (function () {
        return $this->resolve_membership_uuid('org-1', ['membership_uuid' => 'membership-1']);
    })->call($strategy);

    expect($result)->toBe('membership-1');
});

it('returns mismatch error when context membership belongs to another org', function (): void {
    $strategy = new DirectAssignmentStrategy();

    $membershipStub = new class extends MembershipService {
        public function getOrgMembershipData(string $membershipUuid): ?array
        {
            return [
                'data' => [
                    'relationships' => [
                        'organization' => [
                            'data' => ['id' => 'org-2'],
                        ],
                    ],
                ],
            ];
        }
    };

    (function ($stub): void {
        $this->membershipService = $stub;
    })->call($strategy, $membershipStub);

    $result = (function () {
        return $this->resolve_membership_uuid('org-1', ['membership_uuid' => 'membership-1']);
    })->call($strategy);

    expect($result)->toBeInstanceOf(WP_Error::class);
    expect($result->get_error_code())->toBe('membership_org_mismatch');
});

it('falls back to organization membership resolver when context membership is absent', function (): void {
    $strategy = new DirectAssignmentStrategy();

    $membershipStub = new class extends MembershipService {
        public function getOrganizationMembershipUuid(string $orgId): string
        {
            return 'resolved-membership-1';
        }
    };

    (function ($stub): void {
        $this->membershipService = $stub;
    })->call($strategy, $membershipStub);

    $result = (function () {
        return $this->resolve_membership_uuid('org-1', []);
    })->call($strategy);

    expect($result)->toBe('resolved-membership-1');
});
