<?php

declare(strict_types=1);

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\MemberService;

if (!function_exists('wicket_api_client')) {
    function wicket_api_client()
    {
        return $GLOBALS['__orgroster_api_client'] ?? null;
    }
}

beforeEach(function (): void {
    // Default client stub prevents ConnectionService fallback error_log noise
    // in member-list tests that don't care about live connection payloads.
    $GLOBALS['__orgroster_api_client'] = new class {
        public function get(string $endpoint)
        {
            return ['data' => []];
        }
    };
});

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

dataset('roster_modes', [
    'direct' => 'direct',
    'cascade' => 'cascade',
    'groups' => 'groups',
    'membership_cycle' => 'membership_cycle',
]);

it('preserves member search query across roster modes', function (string $mode): void {
    $configStub = new class($mode) extends ConfigService {
        public function __construct(private string $modeValue) {}

        public function get_roster_mode()
        {
            return $this->modeValue;
        }
    };

    $service = new class($configStub) extends MemberService {
        public array $captured = [];

        public function getMembershipMembers(string $membershipUuid, array $args = []): ?array
        {
            $this->captured = [
                'membership_uuid' => $membershipUuid,
                'args' => $args,
            ];

            return [
                'data' => [
                    [
                        'id' => 'pm-1',
                        'type' => 'person_memberships',
                        'attributes' => [
                            'person_first_name' => 'Meri',
                            'person_last_name' => 'Tester',
                            'person_email' => 'meri@example.org',
                        ],
                        'relationships' => [
                            'person' => [
                                'data' => [
                                    'id' => 'person-1',
                                ],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => 'person-1',
                        'type' => 'people',
                        'attributes' => [
                            'given_name' => 'Meri',
                            'family_name' => 'Tester',
                            'email' => 'meri@example.org',
                        ],
                    ],
                ],
                'meta' => [
                    'page' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_count' => 1,
                        'size' => 15,
                    ],
                ],
            ];
        }

        public function getPersonCurrentRolesByOrgId($personUuid, $orgUuid)
        {
            return ['member'];
        }
    };

    $result = $service->search_members('mem-1', 'org-1', 'meri', [
        'page' => 1,
        'size' => 15,
    ]);

    expect($service->captured['membership_uuid'] ?? null)->toBe('mem-1');
    expect($service->captured['args']['query'] ?? null)->toBe('meri');
    expect($result['query'] ?? null)->toBe('meri');
    expect($result['members'] ?? [])->toHaveCount(1);
})->with('roster_modes');

it('passes null query to membership fetch on initial list load', function (): void {
    $service = new class(new ConfigService()) extends MemberService {
        public array $captured = [];

        public function getMembershipMembers(string $membershipUuid, array $args = []): ?array
        {
            $this->captured = $args;

            return [
                'data' => [],
                'meta' => [
                    'page' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_count' => 0,
                        'size' => 15,
                    ],
                ],
            ];
        }
    };

    $service->get_members('mem-1', 'org-1', [
        'page' => 1,
        'size' => 15,
        'query' => '',
    ]);

    expect(array_key_exists('query', $service->captured))->toBeTrue();
    expect($service->captured['query'])->toBeNull();
});

it('blocks role updates for inactive members when activity guard is enabled', function (): void {
    $GLOBALS['__orgroster_api_client'] = new class {
        public function get(string $endpoint)
        {
            return [
                'data' => [
                    [
                        'id' => 'pm-1',
                        'type' => 'person_memberships',
                        'attributes' => [
                            'active' => false,
                        ],
                        'relationships' => [
                            'person' => [
                                'data' => [
                                    'id' => 'person-1',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
    };

    $service = new MemberService(new ConfigService());
    (function (): void {
        $this->config['member_edit']['require_active_membership_for_role_updates'] = true;
    })->call($service);

    $result = $service->update_member_roles('person-1', 'org-1', 'mem-1', ['org_editor']);

    expect(is_wp_error($result))->toBeTrue();
    expect($result->get_error_code())->toBe('inactive_member_role_update_forbidden');

    unset($GLOBALS['__orgroster_api_client']);
});

it('includes inactive relationships on member cards by default', function (): void {
    $service = new class(new ConfigService()) extends MemberService {
        public function getMembershipMembers(string $membershipUuid, array $args = []): ?array
        {
            return [
                'data' => [
                    [
                        'id' => 'pm-1',
                        'type' => 'person_memberships',
                        'attributes' => [
                            'person_first_name' => 'Meri',
                            'person_last_name' => 'Tester',
                            'person_email' => 'meri@example.org',
                        ],
                        'relationships' => [
                            'person' => [
                                'data' => [
                                    'id' => 'person-1',
                                ],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => 'person-1',
                        'type' => 'people',
                        'attributes' => [
                            'given_name' => 'Meri',
                            'family_name' => 'Tester',
                            'email' => 'meri@example.org',
                        ],
                    ],
                ],
                'meta' => [
                    'page' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_count' => 1,
                        'size' => 15,
                    ],
                ],
            ];
        }

        public function getPersonCurrentRolesByOrgId($personUuid, $orgUuid)
        {
            return ['member'];
        }
    };

    $connectionStub = new class extends OrgManagement\Services\ConnectionService {
        public function getPersonConnectionsById($personUuid)
        {
            return [
                'data' => [
                    [
                        'attributes' => [
                            'type' => 'regular_member',
                            'active' => false,
                            'uuid' => 'conn-1',
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => [
                                    'id' => 'org-1',
                                ],
                            ],
                        ],
                    ],
                    [
                        'attributes' => [
                            'type' => 'company_admin',
                            'active' => true,
                            'uuid' => 'conn-2',
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => [
                                    'id' => 'org-1',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
    };

    $connectionProperty = new ReflectionProperty(MemberService::class, 'connectionService');
    $connectionProperty->setValue($service, $connectionStub);

    $result = $service->get_members('mem-1', 'org-1', [
        'page' => 1,
        'size' => 15,
    ]);

    expect($result['members'] ?? [])->toHaveCount(1);
    expect($result['members'][0]['relationship_names'] ?? null)->toBe('Regular Member, Company Admin');
});

it('shows only active relationships on member cards when active-only filter is enabled', function (): void {
    $service = new class(new ConfigService()) extends MemberService {
        public function getMembershipMembers(string $membershipUuid, array $args = []): ?array
        {
            return [
                'data' => [
                    [
                        'id' => 'pm-1',
                        'type' => 'person_memberships',
                        'attributes' => [
                            'person_first_name' => 'Meri',
                            'person_last_name' => 'Tester',
                            'person_email' => 'meri@example.org',
                        ],
                        'relationships' => [
                            'person' => [
                                'data' => [
                                    'id' => 'person-1',
                                ],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => 'person-1',
                        'type' => 'people',
                        'attributes' => [
                            'given_name' => 'Meri',
                            'family_name' => 'Tester',
                            'email' => 'meri@example.org',
                        ],
                    ],
                ],
                'meta' => [
                    'page' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_count' => 1,
                        'size' => 15,
                    ],
                ],
            ];
        }

        public function getPersonCurrentRolesByOrgId($personUuid, $orgUuid)
        {
            return ['member'];
        }
    };

    $connectionStub = new class extends OrgManagement\Services\ConnectionService {
        public function getPersonConnectionsById($personUuid)
        {
            return [
                'data' => [
                    [
                        'attributes' => [
                            'type' => 'regular_member',
                            'active' => false,
                            'uuid' => 'conn-1',
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => [
                                    'id' => 'org-1',
                                ],
                            ],
                        ],
                    ],
                    [
                        'attributes' => [
                            'type' => 'company_admin',
                            'active' => true,
                            'uuid' => 'conn-2',
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => [
                                    'id' => 'org-1',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
    };

    $connectionProperty = new ReflectionProperty(MemberService::class, 'connectionService');
    $connectionProperty->setValue($service, $connectionStub);

    $configProperty = new ReflectionProperty(MemberService::class, 'config');
    $config = $configProperty->getValue($service);
    $config['relationships']['member_card_active_only'] = true;
    $configProperty->setValue($service, $config);

    $result = $service->get_members('mem-1', 'org-1', [
        'page' => 1,
        'size' => 15,
    ]);

    expect($result['members'] ?? [])->toHaveCount(1);
    expect($result['members'][0]['relationship_names'] ?? null)->toBe('Company Admin');
    expect($result['members'][0]['person_connection_ids'] ?? null)->toBe('conn-2');
});

it('merges duplicate raw roster rows for the same person into one member card', function (): void {
    $service = new class(new ConfigService()) extends MemberService {
        public function getMembershipMembers(string $membershipUuid, array $args = []): ?array
        {
            return [
                'data' => [
                    [
                        'id' => 'pm-1',
                        'type' => 'person_memberships',
                        'attributes' => [
                            'person_first_name' => 'Alex',
                            'person_last_name' => 'Casey',
                            'person_email' => 'alex@example.org',
                        ],
                        'relationships' => [
                            'person' => [
                                'data' => ['id' => 'person-1'],
                            ],
                        ],
                    ],
                    [
                        'id' => 'pm-2',
                        'type' => 'person_memberships',
                        'attributes' => [
                            'person_first_name' => 'Alex',
                            'person_last_name' => 'Casey',
                            'person_email' => 'alex@example.org',
                        ],
                        'relationships' => [
                            'person' => [
                                'data' => ['id' => 'person-1'],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => 'person-1',
                        'type' => 'people',
                        'attributes' => [
                            'given_name' => 'Alex',
                            'family_name' => 'Casey',
                            'email' => 'alex@example.org',
                        ],
                    ],
                ],
                'meta' => [
                    'page' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_count' => 2,
                        'size' => 15,
                    ],
                ],
            ];
        }

        public function getPersonCurrentRolesByOrgId($personUuid, $orgUuid)
        {
            return ['member'];
        }
    };

    $connectionStub = new class extends OrgManagement\Services\ConnectionService {
        public function getPersonConnectionsById($personUuid)
        {
            return [
                'data' => [
                    [
                        'attributes' => [
                            'type' => 'company_admin',
                            'active' => true,
                            'uuid' => 'conn-1',
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['id' => 'org-1'],
                            ],
                        ],
                    ],
                    [
                        'attributes' => [
                            'type' => 'regular_member',
                            'active' => true,
                            'uuid' => 'conn-2',
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['id' => 'org-1'],
                            ],
                        ],
                    ],
                ],
            ];
        }
    };

    $connectionProperty = new ReflectionProperty(MemberService::class, 'connectionService');
    $connectionProperty->setValue($service, $connectionStub);

    $result = $service->get_members('mem-1', 'org-1', [
        'page' => 1,
        'size' => 15,
    ]);

    expect($result['members'] ?? [])->toHaveCount(1);
    expect($result['members'][0]['relationship_names'] ?? null)->toBe('Company Admin, Regular Member');
    expect($result['members'][0]['person_connection_ids'] ?? null)->toBe('conn-1,conn-2');
});

it('normalizes affiliation aliases and excludes them from roster relationship display', function (): void {
    $service = new class(new ConfigService()) extends MemberService {
        public function getMembershipMembers(string $membershipUuid, array $args = []): ?array
        {
            return [
                'data' => [
                    [
                        'id' => 'pm-1',
                        'type' => 'person_memberships',
                        'attributes' => [
                            'person_first_name' => 'Meri',
                            'person_last_name' => 'Tester',
                            'person_email' => 'meri@example.org',
                        ],
                        'relationships' => [
                            'person' => [
                                'data' => ['id' => 'person-1'],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => 'person-1',
                        'type' => 'people',
                        'attributes' => [
                            'given_name' => 'Meri',
                            'family_name' => 'Tester',
                            'email' => 'meri@example.org',
                        ],
                    ],
                ],
                'meta' => [
                    'page' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_count' => 1,
                        'size' => 15,
                    ],
                ],
            ];
        }

        public function getPersonCurrentRolesByOrgId($personUuid, $orgUuid)
        {
            return ['member'];
        }
    };

    $connectionStub = new class extends OrgManagement\Services\ConnectionService {
        public function getPersonConnectionsById($personUuid)
        {
            return [
                'data' => [
                    [
                        'attributes' => [
                            'type' => 'Affiliation',
                            'active' => true,
                            'uuid' => 'conn-1',
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['id' => 'org-1'],
                            ],
                        ],
                    ],
                    [
                        'attributes' => [
                            'type' => 'regular_member',
                            'active' => true,
                            'uuid' => 'conn-2',
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['id' => 'org-1'],
                            ],
                        ],
                    ],
                ],
            ];
        }
    };

    $connectionProperty = new ReflectionProperty(MemberService::class, 'connectionService');
    $connectionProperty->setValue($service, $connectionStub);

    $configProperty = new ReflectionProperty(MemberService::class, 'config');
    $config = $configProperty->getValue($service);
    $config['relationships']['exclude_relationship_types'] = ['affiliate'];
    $configProperty->setValue($service, $config);

    $result = $service->get_members('mem-1', 'org-1', [
        'page' => 1,
        'size' => 15,
    ]);

    expect($result['members'] ?? [])->toHaveCount(1);
    expect($result['members'][0]['relationship_names'] ?? null)->toBe('Regular Member');
});

it('filters displayed member roles using allowlist and exclude config', function (): void {
    $service = new class(new ConfigService()) extends MemberService {
        public function getMembershipMembers(string $membershipUuid, array $args = []): ?array
        {
            return [
                'data' => [
                    [
                        'id' => 'pm-1',
                        'type' => 'person_memberships',
                        'attributes' => [
                            'person_first_name' => 'Ari',
                            'person_last_name' => 'Lane',
                            'person_email' => 'ari@example.org',
                            'roles' => [
                                'supplemental_member',
                                'member',
                            ],
                        ],
                        'relationships' => [
                            'person' => [
                                'data' => ['id' => 'person-1'],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => 'person-1',
                        'type' => 'people',
                        'attributes' => [
                            'given_name' => 'Ari',
                            'family_name' => 'Lane',
                            'email' => 'ari@example.org',
                        ],
                    ],
                ],
                'meta' => [
                    'page' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_count' => 1,
                        'size' => 15,
                    ],
                ],
            ];
        }

        public function getPersonCurrentRolesByOrgId($personUuid, $orgUuid)
        {
            return ['membership_owner', 'supplemental_member', 'cchl_member_community', 'member'];
        }
    };

    $connectionStub = new class extends OrgManagement\Services\ConnectionService {
        public function getPersonConnectionsById($personUuid)
        {
            return [
                'data' => [
                    [
                        'attributes' => [
                            'type' => 'regular_member',
                            'active' => true,
                            'uuid' => 'conn-1',
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['id' => 'org-1'],
                            ],
                        ],
                    ],
                ],
            ];
        }
    };

    $connectionProperty = new ReflectionProperty(MemberService::class, 'connectionService');
    $connectionProperty->setValue($service, $connectionStub);

    $configProperty = new ReflectionProperty(MemberService::class, 'config');
    $config = $configProperty->getValue($service);
    $config['ui']['member_list']['display_roles_allowlist'] = ['membership_owner', 'membership_manager', 'org_editor', 'member'];
    $config['ui']['member_list']['display_roles_exclude'] = ['supplemental_member', 'cchlmembercommunity', 'cchl_member_community'];
    $configProperty->setValue($service, $config);

    $result = $service->get_members('mem-1', 'org-1', [
        'page' => 1,
        'size' => 15,
    ]);

    expect($result['members'] ?? [])->toHaveCount(1);
    expect($result['members'][0]['current_roles'] ?? [])->toBe(['membership_owner', 'member']);
    expect($result['members'][0]['roles'] ?? [])->toBe(['member']);
});
