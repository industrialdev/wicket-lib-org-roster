<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use OrgManagement\Services\MembershipService;

if (!function_exists('wicket_api_client')) {
    function wicket_api_client()
    {
        return $GLOBALS['__orgroster_api_client'] ?? null;
    }
}

it('returns active membership uuid when available', function (): void {
    Functions\when('wicket_get_org_memberships')->alias(fn (string $orgUuid): array => [
        [
            'membership' => [
                'attributes' => [
                    'uuid' => 'uuid-active',
                    'active' => true,
                    'in_grace' => false,
                ],
                'id' => 'id-active',
            ],
        ],
        [
            'membership' => [
                'attributes' => [
                    'uuid' => 'uuid-inactive',
                    'active' => false,
                    'in_grace' => false,
                ],
                'id' => 'id-inactive',
            ],
        ],
    ]);

    $service = new MembershipService();

    expect($service->getOrganizationMembershipUuid('org-1'))->toBe('uuid-active');
});

it('returns in-grace membership uuid when active is missing', function (): void {
    Functions\when('wicket_get_org_memberships')->alias(fn (string $orgUuid): array => [
        [
            'membership' => [
                'attributes' => [
                    'uuid' => 'uuid-grace',
                    'active' => false,
                    'in_grace' => true,
                ],
                'id' => 'id-grace',
            ],
        ],
    ]);

    $service = new MembershipService();

    expect($service->getOrganizationMembershipUuid('org-1'))->toBe('uuid-grace');
});

it('falls back to the first membership uuid when none are active', function (): void {
    Functions\when('wicket_get_org_memberships')->alias(fn (string $orgUuid): array => [
        [
            'membership' => [
                'attributes' => [
                    'uuid' => 'uuid-fallback',
                    'active' => false,
                    'in_grace' => false,
                ],
                'id' => 'id-fallback',
            ],
        ],
    ]);

    $service = new MembershipService();

    expect($service->getOrganizationMembershipUuid('org-1'))->toBe('uuid-fallback');
});

it('returns cached membership id when present', function (): void {
    $GLOBALS['__orgroster_current_person_uuid'] = 'person-123';
    $cacheKey = 'orgman_membership_' . md5('person-123_org-9');
    set_transient($cacheKey, 'cached-membership', 300);

    $service = new MembershipService();
    (function (): void {
        $this->config['cache']['enabled'] = true;
    })->call($service);

    expect($service->getMembershipForOrganization('org-9'))->toBe('cached-membership');
});

it('fetches org membership data via API when cache enabled', function (): void {
    $GLOBALS['__orgroster_api_client'] = new class {
        public int $calls = 0;

        public function get(string $path)
        {
            $this->calls++;

            return ['data' => ['id' => 'membership-1']];
        }
    };

    $service = new MembershipService();
    (function (): void {
        $this->config['cache']['enabled'] = true;
    })->call($service);

    $result = $service->getOrgMembershipData('membership-1');

    expect($result)->toBe(['data' => ['id' => 'membership-1']])
        ->and($GLOBALS['__orgroster_api_client']->calls)->toBe(1);
});

it('returns cached org membership data when present', function (): void {
    $cacheKey = 'orgman_membership_data_' . md5('membership-2');
    set_transient($cacheKey, ['data' => ['id' => 'cached']], 300);

    $service = new MembershipService();
    (function (): void {
        $this->config['cache']['enabled'] = true;
    })->call($service);

    $result = $service->getOrgMembershipData('membership-2');

    expect($result)->toBe(['data' => ['id' => 'cached']]);
});

it('uses configured tier mapping for effective max assignments', function (): void {
    $service = new MembershipService();
    (function (): void {
        $this->config['seat_policy']['tier_max_assignments'] = [
            'MAS Sustaining' => 3,
            'Joint Sustaining' => 6,
        ];
        $this->config['seat_policy']['tier_name_case_sensitive'] = false;
    })->call($service);

    $membershipData = [
        'data' => [
            'attributes' => [
                'max_assignments' => 99,
            ],
            'relationships' => [
                'membership' => [
                    'data' => [
                        'id' => 'tier-1',
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'id' => 'tier-1',
                'type' => 'memberships',
                'attributes' => [
                    'name' => 'mas sustaining',
                ],
            ],
        ],
    ];

    expect($service->getEffectiveMaxAssignments($membershipData))->toBe(3);
});

it('falls back to api max assignments when tier mapping does not match', function (): void {
    $service = new MembershipService();
    (function (): void {
        $this->config['seat_policy']['tier_max_assignments'] = [
            'Joint Sustaining' => 6,
        ];
    })->call($service);

    $membershipData = [
        'data' => [
            'attributes' => [
                'max_assignments' => 4,
            ],
            'relationships' => [
                'membership' => [
                    'data' => [
                        'id' => 'tier-1',
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'id' => 'tier-1',
                'type' => 'memberships',
                'attributes' => [
                    'name' => 'Sustaining',
                ],
            ],
        ],
    ];

    expect($service->getEffectiveMaxAssignments($membershipData))->toBe(4);
});
