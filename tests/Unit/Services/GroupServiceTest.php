<?php

declare(strict_types=1);

use OrgManagement\Services\GroupService;

if (!function_exists('wicket_api_client')) {
    function wicket_api_client()
    {
        return $GLOBALS['__orgroster_api_client'] ?? null;
    }
}

if (!function_exists('wicket_get_group_members')) {
    function wicket_get_group_members(string $group_uuid, array $args = [])
    {
        return $GLOBALS['__orgroster_group_members_response'] ?? null;
    }
}

if (!function_exists('wicket_search_group_members')) {
    function wicket_search_group_members(string $group_uuid, string $query, array $args = [])
    {
        return $GLOBALS['__orgroster_group_members_response'] ?? null;
    }
}

if (!function_exists('wicket_get_organization')) {
    function wicket_get_organization(string $org_identifier)
    {
        if (!isset($GLOBALS['__orgroster_wicket_get_organization_calls'])) {
            $GLOBALS['__orgroster_wicket_get_organization_calls'] = [];
        }
        $GLOBALS['__orgroster_wicket_get_organization_calls'][] = $org_identifier;

        return $GLOBALS['__orgroster_organization_lookup'][$org_identifier] ?? null;
    }
}

beforeEach(function (): void {
    unset($GLOBALS['__orgroster_api_client']);
    unset($GLOBALS['__orgroster_group_members_response']);
    unset($GLOBALS['__orgroster_organization_lookup']);
    unset($GLOBALS['__orgroster_wicket_get_organization_calls']);
});

it('includes manageable group when tags are only available from group details endpoint', function (): void {
    $GLOBALS['__orgroster_api_client'] = new class {
        public array $calls = [];

        public function get(string $path): array
        {
            $this->calls[] = $path;

            return [
                'data' => [
                    'attributes' => [
                        'tags' => ['Roster Management'],
                    ],
                ],
            ];
        }
    };

    $response = [
        'data' => [
            [
                'attributes' => [
                    'type' => 'president',
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'id' => 'group-1',
                        ],
                    ],
                    'organization' => [
                        'data' => [
                            'id' => 'org-1',
                        ],
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'type' => 'groups',
                'id' => 'group-1',
                'attributes' => [
                    'name' => 'Group Associate 01',
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
                'type' => 'organizations',
                'id' => 'org-1',
                'attributes' => [
                    'name' => 'Canadian Institute of Actuaries',
                ],
            ],
        ],
        'meta' => [
            'page' => [
                'number' => 1,
                'size' => 20,
                'total_pages' => 1,
                'total_items' => 1,
            ],
        ],
    ];

    $service = new class($response) extends GroupService {
        private array $mockResponse;

        public function __construct(array $mockResponse)
        {
            parent::__construct();
            $this->mockResponse = $mockResponse;
        }

        public function get_person_group_memberships(string $person_uuid, array $args = [])
        {
            return $this->mockResponse;
        }
    };

    $result = $service->get_manageable_groups('person-1');

    expect($result['data'])->toHaveCount(1)
        ->and($result['data'][0]['org_uuid'])->toBe('org-1')
        ->and($GLOBALS['__orgroster_api_client']->calls)->toContain('/groups/group-1');
});

it('allows delegate role to manage groups by default config', function (): void {
    $response = [
        'data' => [
            [
                'attributes' => [
                    'type' => 'delegate',
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'id' => 'group-2',
                        ],
                    ],
                    'organization' => [
                        'data' => [
                            'id' => 'org-2',
                        ],
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'type' => 'groups',
                'id' => 'group-2',
                'attributes' => [
                    'name' => 'Roster Group',
                    'tags' => ['Roster Management'],
                ],
                'relationships' => [
                    'organization' => [
                        'data' => [
                            'id' => 'org-2',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'organizations',
                'id' => 'org-2',
                'attributes' => [
                    'name' => 'Org Two',
                ],
            ],
        ],
    ];

    $service = new class($response) extends GroupService {
        private array $mockResponse;

        public function __construct(array $mockResponse)
        {
            parent::__construct();
            $this->mockResponse = $mockResponse;
        }

        public function get_person_group_memberships(string $person_uuid, array $args = [])
        {
            return $this->mockResponse;
        }
    };

    $result = $service->get_manageable_groups('person-2');

    expect($result['data'])->toHaveCount(1)
        ->and($result['data'][0]['role_slug'])->toBe('delegate');
});

it('includes non-manage roles on groups landing when include_all_roles is enabled', function (): void {
    $response = [
        'data' => [
            [
                'attributes' => [
                    'type' => 'delegate',
                ],
                'relationships' => [
                    'group' => [
                        'data' => ['id' => 'group-manage'],
                    ],
                    'organization' => [
                        'data' => ['id' => 'org-1'],
                    ],
                ],
            ],
            [
                'attributes' => [
                    'type' => 'observer',
                ],
                'relationships' => [
                    'group' => [
                        'data' => ['id' => 'group-visible'],
                    ],
                    'organization' => [
                        'data' => ['id' => 'org-1'],
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'type' => 'groups',
                'id' => 'group-manage',
                'attributes' => [
                    'name' => 'Manageable Group',
                    'tags' => ['Roster Management'],
                ],
                'relationships' => [
                    'organization' => [
                        'data' => ['id' => 'org-1'],
                    ],
                ],
            ],
            [
                'type' => 'groups',
                'id' => 'group-visible',
                'attributes' => [
                    'name' => 'Visible Group',
                    'tags' => ['Roster Management'],
                ],
                'relationships' => [
                    'organization' => [
                        'data' => ['id' => 'org-1'],
                    ],
                ],
            ],
            [
                'type' => 'organizations',
                'id' => 'org-1',
                'attributes' => [
                    'name' => 'Org One',
                ],
            ],
        ],
    ];

    $service = new class($response) extends GroupService {
        private array $mockResponse;

        public function __construct(array $mockResponse)
        {
            parent::__construct();
            $this->mockResponse = $mockResponse;
        }

        public function get_person_group_memberships(string $person_uuid, array $args = [])
        {
            return $this->mockResponse;
        }
    };

    $result = $service->get_manageable_groups('person-3', [
        'include_all_roles' => true,
    ]);
    $byGroup = [];
    foreach ($result['data'] as $item) {
        $byGroup[(string) ($item['group']['id'] ?? '')] = $item;
    }

    expect($result['data'])->toHaveCount(2);
    expect($byGroup['group-manage']['can_manage'] ?? null)->toBeTrue();
    expect($byGroup['group-visible']['can_manage'] ?? null)->toBeFalse();
    expect($byGroup['group-visible']['role_slug'] ?? null)->toBe('observer');
});

it('keeps non-manage roles excluded when include_all_roles is not enabled', function (): void {
    $response = [
        'data' => [
            [
                'attributes' => [
                    'type' => 'delegate',
                ],
                'relationships' => [
                    'group' => [
                        'data' => ['id' => 'group-manage'],
                    ],
                    'organization' => [
                        'data' => ['id' => 'org-1'],
                    ],
                ],
            ],
            [
                'attributes' => [
                    'type' => 'observer',
                ],
                'relationships' => [
                    'group' => [
                        'data' => ['id' => 'group-visible'],
                    ],
                    'organization' => [
                        'data' => ['id' => 'org-1'],
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'type' => 'groups',
                'id' => 'group-manage',
                'attributes' => [
                    'name' => 'Manageable Group',
                    'tags' => ['Roster Management'],
                ],
                'relationships' => [
                    'organization' => [
                        'data' => ['id' => 'org-1'],
                    ],
                ],
            ],
            [
                'type' => 'groups',
                'id' => 'group-visible',
                'attributes' => [
                    'name' => 'Visible Group',
                    'tags' => ['Roster Management'],
                ],
                'relationships' => [
                    'organization' => [
                        'data' => ['id' => 'org-1'],
                    ],
                ],
            ],
        ],
    ];

    $service = new class($response) extends GroupService {
        private array $mockResponse;

        public function __construct(array $mockResponse)
        {
            parent::__construct();
            $this->mockResponse = $mockResponse;
        }

        public function get_person_group_memberships(string $person_uuid, array $args = [])
        {
            return $this->mockResponse;
        }
    };

    $result = $service->get_manageable_groups('person-4');

    expect($result['data'])->toHaveCount(1);
    expect($result['data'][0]['group']['id'] ?? null)->toBe('group-manage');
    expect($result['data'][0]['can_manage'] ?? null)->toBeTrue();
});

it('keeps tagged group in results even when organization relationship is missing', function (): void {
    $response = [
        'data' => [
            [
                'id' => 'membership-1',
                'attributes' => [
                    'type' => 'delegate',
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'id' => 'group-keep',
                        ],
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'type' => 'groups',
                'id' => 'group-keep',
                'attributes' => [
                    'name' => 'Tagged Group',
                    'tags' => ['Roster Management'],
                ],
                'relationships' => [],
            ],
        ],
    ];

    $service = new class($response) extends GroupService {
        private array $mockResponse;

        public function __construct(array $mockResponse)
        {
            parent::__construct();
            $this->mockResponse = $mockResponse;
        }

        public function get_person_group_memberships(string $person_uuid, array $args = [])
        {
            return $this->mockResponse;
        }
    };

    $result = $service->get_manageable_groups('person-5');

    expect($result['data'])->toHaveCount(1);
    expect($result['data'][0]['group']['id'] ?? null)->toBe('group-keep');
    expect($result['data'][0]['org_uuid'] ?? null)->toBe('');
});

it('resolves group management org scope from group relationship when membership org is empty', function (): void {
    $response = [
        'data' => [
            [
                'attributes' => [
                    'type' => 'delegate',
                    'custom_data_field' => [
                        'key' => 'association',
                        'value' => ['name' => 'Canadian Institute of Actuaries'],
                    ],
                ],
                'relationships' => [
                    'group' => [
                        'data' => ['id' => 'group-1'],
                    ],
                    'organization' => [
                        'data' => ['id' => ''],
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'type' => 'groups',
                'id' => 'group-1',
                'attributes' => [
                    'name' => 'Committee Group',
                    'tags' => ['Roster Management'],
                ],
                'relationships' => [
                    'organization' => [
                        'data' => ['id' => 'org-fallback'],
                    ],
                ],
            ],
        ],
    ];

    $service = new class($response) extends GroupService {
        private array $mockResponse;

        public function __construct(array $mockResponse)
        {
            parent::__construct();
            $this->mockResponse = $mockResponse;
        }

        public function get_person_group_memberships(string $person_uuid, array $args = [])
        {
            return $this->mockResponse;
        }
    };

    $access = $service->can_manage_group('group-1', 'person-6');

    expect($access['allowed'])->toBeTrue();
    expect($access['org_uuid'])->toBe('org-fallback');
    expect($access['org_identifier'])->toBe('Canadian Institute of Actuaries');
    expect($access['role_slug'])->toBe('delegate');
});

it('matches group members by normalized org scope tokens for listing and remove lookup', function (): void {
    $GLOBALS['__orgroster_organization_lookup'] = [
        'ab89ceb6-a1cf-4e95-9471-e637d6fd7cc2' => [
            'data' => [
                'id' => 'ab89ceb6-a1cf-4e95-9471-e637d6fd7cc2',
                'attributes' => [
                    'legal_name' => 'Canadian Institute of Actuaries',
                ],
            ],
        ],
    ];

    $GLOBALS['__orgroster_group_members_response'] = [
        'data' => [
            [
                'id' => 'gm-1',
                'attributes' => [
                    'type' => 'member',
                    'custom_data_field' => [
                        'key' => 'association',
                        'value' => ['name' => 'Canadian Institute of Actuaries'],
                    ],
                ],
                'relationships' => [
                    'person' => [
                        'data' => ['id' => 'person-1'],
                    ],
                    'organization' => [
                        'data' => ['id' => ''],
                    ],
                ],
            ],
            [
                'id' => 'gm-2',
                'attributes' => [
                    'type' => 'member',
                    'custom_data_field' => [
                        'key' => 'association',
                        'value' => ['name' => 'Another Org'],
                    ],
                ],
                'relationships' => [
                    'person' => [
                        'data' => ['id' => 'person-2'],
                    ],
                    'organization' => [
                        'data' => ['id' => 'org-other'],
                    ],
                ],
            ],
        ],
        'included' => [
            [
                'type' => 'people',
                'id' => 'person-1',
                'attributes' => [
                    'given_name' => 'Terry',
                    'family_name' => 'Applebly',
                    'email' => 'terry@example.org',
                ],
            ],
            [
                'type' => 'people',
                'id' => 'person-2',
                'attributes' => [
                    'given_name' => 'Other',
                    'family_name' => 'Person',
                    'email' => 'other@example.org',
                ],
            ],
        ],
        'meta' => [
            'page' => [
                'number' => 1,
                'size' => 15,
                'total_pages' => 1,
                'total_items' => 2,
            ],
        ],
    ];

    $service = new GroupService();

    $members = $service->get_group_members('group-1', 'ab89ceb6-a1cf-4e95-9471-e637d6fd7cc2', [
        'page' => 1,
        'size' => 15,
    ]);
    $matchId = $service->find_group_member_id(
        'group-1',
        'person-1',
        'ab89ceb6-a1cf-4e95-9471-e637d6fd7cc2'
    );

    expect($members['members'])->toHaveCount(1);
    expect($members['members'][0]['person_uuid'] ?? null)->toBe('person-1');
    expect($matchId)->toBe('gm-1');
    expect($GLOBALS['__orgroster_wicket_get_organization_calls'] ?? [])->toContain('ab89ceb6-a1cf-4e95-9471-e637d6fd7cc2');
});
