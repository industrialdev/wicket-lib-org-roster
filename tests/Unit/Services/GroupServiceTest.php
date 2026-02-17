<?php

declare(strict_types=1);

use OrgManagement\Services\GroupService;

if (!function_exists('wicket_api_client')) {
    function wicket_api_client()
    {
        return $GLOBALS['__orgroster_api_client'] ?? null;
    }
}

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
