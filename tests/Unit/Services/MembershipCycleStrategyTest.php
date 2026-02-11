<?php

declare(strict_types=1);

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\MemberService;
use OrgManagement\Services\MembershipService;
use OrgManagement\Services\Strategies\MembershipCycleStrategy;
use OrgManagement\Services\Strategies\RosterManagementStrategy;

it('returns error when membership uuid is missing on membership cycle add', function (): void {
    $strategy = new MembershipCycleStrategy();

    $result = $strategy->add_member('org-1', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ], []);

    expect($result)->toBeInstanceOf(WP_Error::class);
    expect($result->get_error_code())->toBe('missing_membership_uuid');
});

it('returns error when person membership id is missing on membership cycle remove', function (): void {
    $strategy = new MembershipCycleStrategy();

    $result = $strategy->remove_member('org-1', 'person-1', [
        'membership_uuid' => 'membership-1',
    ]);

    expect($result)->toBeInstanceOf(WP_Error::class);
    expect($result->get_error_code())->toBe('missing_person_membership_id');
});

it('routes membership cycle mode through configured strategy map', function (): void {
    $configStub = new class extends ConfigService {
        public function get_roster_mode()
        {
            return 'membership_cycle';
        }
    };

    $service = new MemberService($configStub);

    $strategyStub = new class implements RosterManagementStrategy {
        public function add_member($org_id, $member_data, $context = [])
        {
            return ['status' => 'ok', 'strategy' => 'membership_cycle'];
        }

        public function remove_member($org_id, $person_uuid, $context = [])
        {
            return ['status' => 'ok', 'strategy' => 'membership_cycle'];
        }
    };

    (function ($stub): void {
        $this->strategies['membership_cycle'] = $stub;
    })->call($service, $strategyStub);

    $result = $service->add_member('org-1', ['email' => 'test@example.com'], []);

    expect($result)->toBeArray();
    expect($result['strategy'] ?? null)->toBe('membership_cycle');
});

it('extracts membership uuid from membership_id fallback', function (): void {
    $strategy = new MembershipCycleStrategy();

    $result = (function () {
        return $this->extract_membership_uuid(['membership_id' => 'membership-42']);
    })->call($strategy);

    expect($result)->toBe('membership-42');
});

it('fails scope validation when membership belongs to a different org', function (): void {
    $strategy = new MembershipCycleStrategy();

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
        return $this->validate_membership_scope('org-1', 'membership-1');
    })->call($strategy);

    expect($result)->toBeInstanceOf(WP_Error::class);
    expect($result->get_error_code())->toBe('membership_org_mismatch');
});
