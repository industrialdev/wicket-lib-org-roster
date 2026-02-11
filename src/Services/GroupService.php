<?php

/**
 * Group Service for Org Management.
 */

namespace OrgManagement\Services;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class GroupService
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \WC_Logger|null
     */
    private $logger = null;

    public function __construct()
    {
        $this->config = \OrgManagement\Config\get_config();
    }

    /**
     * Get groups config with defaults applied.
     *
     * @return array
     */
    private function get_groups_config(): array
    {
        $groups = $this->config['groups'] ?? [];

        return is_array($groups) ? $groups : [];
    }

    /**
     * Get manage roles.
     *
     * @return array
     */
    public function get_manage_roles(): array
    {
        $roles = $this->get_groups_config()['manage_roles'] ?? [];
        if (!is_array($roles)) {
            $roles = [];
        }
        $roles = array_values(array_filter(array_map('sanitize_key', $roles)));
        $this->get_logger()->debug('[OrgRoster] Manage roles resolved', [
            'source' => 'wicket-orgroster',
            'roles' => $roles,
        ]);

        return $roles;
    }

    /**
     * Get roster roles.
     *
     * @return array
     */
    public function get_roster_roles(): array
    {
        $roles = $this->get_groups_config()['roster_roles'] ?? [];
        if (!is_array($roles)) {
            $roles = [];
        }
        $roles = array_values(array_filter(array_map('sanitize_key', $roles)));
        $this->get_logger()->debug('[OrgRoster] Roster roles resolved', [
            'source' => 'wicket-orgroster',
            'roles' => $roles,
        ]);

        return $roles;
    }

    /**
     * Get roster management tag name.
     *
     * @return string
     */
    public function get_roster_tag_name(): string
    {
        $tag = (string) ($this->get_groups_config()['tag_name'] ?? '');
        $tag = trim($tag);
        $this->get_logger()->debug('[OrgRoster] Roster tag name', [
            'source' => 'wicket-orgroster',
            'tag' => $tag,
        ]);

        return $tag;
    }

    /**
     * Determine if a group should be included based on tag.
     *
     * @param array $group
     * @return bool
     */
    private function group_has_roster_tag(array $group): bool
    {
        $tag = $this->get_roster_tag_name();
        if ('' === $tag) {
            return true;
        }

        $tags = $group['attributes']['tags'] ?? [];
        if (!is_array($tags)) {
            return false;
        }

        $case_sensitive = (bool) ($this->get_groups_config()['tag_case_sensitive'] ?? true);
        foreach ($tags as $tag_value) {
            $tag_value = is_string($tag_value) ? $tag_value : '';
            if ($case_sensitive && $tag_value === $tag) {
                return true;
            }
            if (!$case_sensitive && strcasecmp($tag_value, $tag) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get page size for group list.
     *
     * @return int
     */
    public function get_group_list_page_size(): int
    {
        $size = (int) ($this->get_groups_config()['list']['page_size'] ?? 20);

        return max(1, $size);
    }

    /**
     * Get page size for group member list.
     *
     * @return int
     */
    public function get_group_member_page_size(): int
    {
        $size = (int) ($this->get_groups_config()['list']['member_page_size'] ?? 15);

        return max(1, $size);
    }

    /**
     * Get current user group memberships.
     *
     * @param string $person_uuid
     * @param array  $args
     * @return array|false
     */
    public function get_person_group_memberships(string $person_uuid, array $args = [])
    {
        if (empty($person_uuid) || !function_exists('wicket_api_client')) {
            return false;
        }

        $page = max(1, (int) ($args['page'] ?? 1));
        $size = max(1, (int) ($args['size'] ?? $this->get_group_list_page_size()));
        $active = isset($args['active']) ? (bool) $args['active'] : true;

        $endpoint = '/group_members';
        $query = [
            'include' => 'group,organization',
            'page' => [
                'number' => $page,
                'size' => $size,
            ],
            'filter' => [
                'active_true' => $active ? 'true' : 'false',
                'person_uuid_eq' => $person_uuid,
            ],
        ];

        try {
            $client = wicket_api_client();
            $this->get_logger()->info('[OrgRoster] Fetching group memberships', [
                'source' => 'wicket-orgroster',
                'person_uuid' => $person_uuid,
                'endpoint' => $endpoint,
                'page' => $page,
                'size' => $size,
                'active' => $active,
            ]);

            return $client->get($endpoint, ['query' => $query]);
        } catch (\Throwable $e) {
            $this->get_logger()->error('[OrgRoster] Failed fetching group memberships', [
                'source' => 'wicket-orgroster',
                'person_uuid' => $person_uuid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build lookup map from included resources.
     *
     * @param array $included
     * @return array
     */
    private function build_included_lookup(array $included): array
    {
        $lookup = [];
        foreach ($included as $item) {
            $type = $item['type'] ?? '';
            $id = $item['id'] ?? '';
            if ($type && $id) {
                if (!isset($lookup[$type])) {
                    $lookup[$type] = [];
                }
                $lookup[$type][$id] = $item;
            }
        }

        return $lookup;
    }

    /**
     * Extract organization identifier from group membership custom_data_field.
     *
     * @param array $group_membership
     * @param string $fallback_org_uuid
     * @return string
     */
    public function extract_org_identifier(array $group_membership, string $fallback_org_uuid = ''): string
    {
        $info = $group_membership['attributes']['custom_data_field'] ?? null;
        $config = $this->get_groups_config()['additional_info'] ?? [];
        $expected_key = isset($config['key']) ? (string) $config['key'] : '';
        $value_field = isset($config['value_field']) ? (string) $config['value_field'] : '';
        $fallback_to_org_uuid = (bool) ($config['fallback_to_org_uuid'] ?? true);

        if (is_array($info)) {
            $key = $info['key'] ?? '';
            if ($expected_key === '' || $key === $expected_key) {
                $value = $info['value'] ?? null;
                if ('' !== $value_field && is_array($value) && isset($value[$value_field])) {
                    $value = $value[$value_field];
                }
                if (is_string($value) && '' !== trim($value)) {
                    return trim($value);
                }
            }
        }

        if ($fallback_to_org_uuid && $fallback_org_uuid) {
            return $fallback_org_uuid;
        }

        return '';
    }

    /**
     * Build custom_data_field payload for group membership.
     *
     * @param string $org_identifier
     * @return array|null
     */
    public function build_custom_data_field(string $org_identifier): ?array
    {
        if ('' === $org_identifier) {
            return null;
        }

        $config = $this->get_groups_config()['additional_info'] ?? [];
        $key = isset($config['key']) ? (string) $config['key'] : '';
        $value_field = isset($config['value_field']) ? (string) $config['value_field'] : '';

        if ('' === $key) {
            return null;
        }

        $value = '' !== $value_field ? [$value_field => $org_identifier] : $org_identifier;

        return [
            'key' => $key,
            'value' => $value,
        ];
    }

    /**
     * Get manageable groups for person.
     *
     * @param string $person_uuid
     * @param array  $args
     * @return array
     */
    public function get_manageable_groups(string $person_uuid, array $args = []): array
    {
        $page = max(1, (int) ($args['page'] ?? 1));
        $size = max(1, (int) ($args['size'] ?? $this->get_group_list_page_size()));
        $query = isset($args['query']) ? sanitize_text_field((string) $args['query']) : '';

        $response = $this->get_person_group_memberships($person_uuid, [
            'page' => $page,
            'size' => $size,
            'active' => true,
        ]);

        $groups = [];
        $meta = [
            'page' => [
                'number' => $page,
                'size' => $size,
                'total_pages' => 1,
                'total_items' => 0,
            ],
        ];

        if (!is_array($response) || empty($response['data'])) {
            $this->get_logger()->debug('[OrgRoster] No group memberships found', [
                'source' => 'wicket-orgroster',
                'person_uuid' => $person_uuid,
            ]);

            return ['data' => $groups, 'meta' => $meta];
        }

        $included_lookup = $this->build_included_lookup($response['included'] ?? []);
        $manage_roles = $this->get_manage_roles();

        foreach ($response['data'] as $membership) {
            $role_slug = sanitize_key((string) ($membership['attributes']['type'] ?? ''));
            if (!in_array($role_slug, $manage_roles, true)) {
                continue;
            }

            $group_id = $membership['relationships']['group']['data']['id'] ?? '';
            if (!$group_id || empty($included_lookup['groups'][$group_id])) {
                continue;
            }

            $group = $included_lookup['groups'][$group_id];
            $group_attrs = is_array($group) ? ($group['attributes'] ?? []) : [];
            $group_tags = is_array($group_attrs) ? ($group_attrs['tags'] ?? null) : null;
            $this->get_logger()->debug('[OrgRoster] Group membership included group tags', [
                'source' => 'wicket-orgroster',
                'group_id' => $group_id,
                'tags_present' => is_array($group_tags),
                'tags' => is_array($group_tags) ? $group_tags : null,
            ]);
            if ((!is_array($group_tags) || empty($group_tags)) && function_exists('wicket_api_client')) {
                static $debug_tag_fetch_count = 0;
                if ($debug_tag_fetch_count < 3) {
                    $debug_tag_fetch_count++;
                    try {
                        $details = wicket_api_client()->get('/groups/' . rawurlencode($group_id));
                        $detail_attrs = is_array($details) ? ($details['data']['attributes'] ?? []) : [];
                        $this->get_logger()->debug('[OrgRoster] Group detail tag inspection', [
                            'source' => 'wicket-orgroster',
                            'group_id' => $group_id,
                            'attribute_keys' => is_array($detail_attrs) ? array_keys($detail_attrs) : null,
                            'tags' => is_array($detail_attrs) ? ($detail_attrs['tags'] ?? null) : null,
                        ]);
                    } catch (\Throwable $e) {
                        $this->get_logger()->error('[OrgRoster] Group detail tag inspection failed', [
                            'source' => 'wicket-orgroster',
                            'group_id' => $group_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            if (!$this->group_has_roster_tag($group)) {
                $this->get_logger()->debug('[OrgRoster] Group skipped by tag filter', [
                    'source' => 'wicket-orgroster',
                    'group_id' => $group_id,
                    'tags' => is_array($group_tags) ? $group_tags : null,
                ]);
                continue;
            }

            $org_id = $membership['relationships']['organization']['data']['id'] ?? '';
            if (empty($org_id)) {
                $org_id = $group['relationships']['organization']['data']['id'] ?? '';
            }
            if (empty($org_id)) {
                $this->get_logger()->debug('[OrgRoster] Group skipped missing organization relationship', [
                    'source' => 'wicket-orgroster',
                    'group_id' => $group_id,
                ]);
                continue; // must be attached to an organization
            }

            $org_identifier = $this->extract_org_identifier($membership, $org_id);
            $organization = $included_lookup['organizations'][$org_id] ?? null;
            $org_name = '';
            if (is_array($organization)) {
                $org_attrs = $organization['attributes'] ?? [];
                $org_name = $org_attrs['legal_name'] ?? $org_attrs['legal_name_en'] ?? $org_attrs['name'] ?? '';
            }
            $group_name = $group['attributes']['name'] ?? $group['attributes']['name_en'] ?? $group['attributes']['name_fr'] ?? '';

            if ($query !== '') {
                $haystack = strtolower($group_name);
                if (false === strpos($haystack, strtolower($query))) {
                    continue;
                }
            }

            $groups[] = [
                'group' => $group,
                'group_membership' => $membership,
                'org_uuid' => $org_id,
                'org_identifier' => $org_identifier,
                'org_name' => $org_name,
                'role_slug' => $role_slug,
            ];
        }

        $page_meta = $response['meta']['page'] ?? [];
        if (is_array($page_meta)) {
            $meta['page'] = array_merge($meta['page'], $page_meta);
        }

        $meta['page']['total_items'] ??= count($groups);

        $this->get_logger()->info('[OrgRoster] Manageable groups resolved', [
            'source' => 'wicket-orgroster',
            'person_uuid' => $person_uuid,
            'count' => count($groups),
            'page' => $page,
            'size' => $size,
        ]);

        return [
            'data' => $groups,
            'meta' => $meta,
        ];
    }

    /**
     * Check whether current user can manage a group.
     *
     * @param string $group_uuid
     * @param string $person_uuid
     * @return array{allowed: bool, org_uuid: string, org_identifier: string, role_slug: string}
     */
    public function can_manage_group(string $group_uuid, string $person_uuid): array
    {
        $result = [
            'allowed' => false,
            'org_uuid' => '',
            'org_identifier' => '',
            'role_slug' => '',
        ];

        if (empty($group_uuid) || empty($person_uuid)) {
            return $result;
        }

        $memberships = $this->get_person_group_memberships($person_uuid, [
            'page' => 1,
            'size' => $this->get_group_list_page_size(),
            'active' => true,
        ]);

        if (!is_array($memberships) || empty($memberships['data'])) {
            $this->get_logger()->debug('[OrgRoster] No memberships for group access check', [
                'source' => 'wicket-orgroster',
                'group_uuid' => $group_uuid,
                'person_uuid' => $person_uuid,
            ]);

            return $result;
        }

        $included_lookup = $this->build_included_lookup($memberships['included'] ?? []);
        $manage_roles = $this->get_manage_roles();
        foreach ($memberships['data'] as $membership) {
            $role_slug = sanitize_key((string) ($membership['attributes']['type'] ?? ''));
            if (!in_array($role_slug, $manage_roles, true)) {
                continue;
            }

            $membership_group = $membership['relationships']['group']['data']['id'] ?? '';
            if ($membership_group !== $group_uuid) {
                continue;
            }

            $org_id = $membership['relationships']['organization']['data']['id'] ?? '';
            if (empty($org_id) && $membership_group && isset($included_lookup['groups'][$membership_group])) {
                $group_item = $included_lookup['groups'][$membership_group];
                $org_id = $group_item['relationships']['organization']['data']['id'] ?? $org_id;
            }
            $org_identifier = $this->extract_org_identifier($membership, $org_id);

            $result = [
                'allowed' => true,
                'org_uuid' => $org_id,
                'org_identifier' => $org_identifier,
                'role_slug' => $role_slug,
            ];
            break;
        }

        $this->get_logger()->info('[OrgRoster] Group access evaluated', [
            'source' => 'wicket-orgroster',
            'group_uuid' => $group_uuid,
            'person_uuid' => $person_uuid,
            'allowed' => $result['allowed'],
            'org_uuid' => $result['org_uuid'],
            'role_slug' => $result['role_slug'],
        ]);

        return $result;
    }

    /**
     * Fetch group members list (roster roles only), filtered by org identifier.
     *
     * @param string $group_uuid
     * @param string $org_identifier
     * @param array  $args
     * @return array
     */
    public function get_group_members(string $group_uuid, string $org_identifier, array $args = []): array
    {
        $page = max(1, (int) ($args['page'] ?? 1));
        $size = max(1, (int) ($args['size'] ?? $this->get_group_member_page_size()));
        $query = isset($args['query']) ? sanitize_text_field((string) $args['query']) : '';

        $roles = $this->get_roster_roles();
        $role_param = implode(',', $roles);

        $response = null;
        if ($query !== '' && function_exists('wicket_search_group_members')) {
            $response = wicket_search_group_members($group_uuid, $query, [
                'per_page' => $size,
                'page' => $page,
                'active' => true,
                'role' => $role_param,
            ]);
        } elseif (function_exists('wicket_get_group_members')) {
            $response = wicket_get_group_members($group_uuid, [
                'per_page' => $size,
                'page' => $page,
                'active' => true,
                'role' => $role_param,
            ]);
        }

        $this->get_logger()->debug('[OrgRoster] Group members fetch', [
            'source' => 'wicket-orgroster',
            'group_uuid' => $group_uuid,
            'page' => $page,
            'size' => $size,
            'query' => $query,
            'org_identifier' => $org_identifier,
        ]);

        return $this->normalize_group_members_response($response, $org_identifier, [
            'page' => $page,
            'size' => $size,
            'query' => $query,
        ]);
    }

    /**
     * Find group member id for a person and role within a group.
     *
     * @param string $group_uuid
     * @param string $person_uuid
     * @param string $org_identifier
     * @param array  $roles
     * @return string
     */
    public function find_group_member_id(string $group_uuid, string $person_uuid, string $org_identifier, array $roles = []): string
    {
        if (empty($group_uuid) || empty($person_uuid)) {
            return '';
        }

        $roles = !empty($roles) ? $roles : $this->get_roster_roles();
        $role_param = implode(',', $roles);

        $response = null;
        if (function_exists('wicket_get_group_members')) {
            $response = wicket_get_group_members($group_uuid, [
                'per_page' => 100,
                'page' => 1,
                'active' => true,
                'role' => $role_param,
            ]);
        }

        if (!is_array($response) || empty($response['data'])) {
            return '';
        }

        foreach ($response['data'] as $item) {
            $member_person = $item['relationships']['person']['data']['id'] ?? '';
            if ($member_person !== $person_uuid) {
                continue;
            }

            $member_org_identifier = $this->extract_org_identifier($item, $org_identifier);
            if ('' !== $org_identifier && $member_org_identifier !== $org_identifier) {
                continue;
            }

            return (string) ($item['id'] ?? '');
        }

        return '';
    }

    /**
     * Normalize group member response.
     *
     * @param array|\WP_Error|null $response
     * @param string $org_identifier
     * @param array $context
     * @return array
     */
    private function normalize_group_members_response($response, string $org_identifier, array $context): array
    {
        $page = (int) ($context['page'] ?? 1);
        $size = (int) ($context['size'] ?? $this->get_group_member_page_size());
        $query = (string) ($context['query'] ?? '');

        $members = [];
        $pagination = [
            'currentPage' => $page,
            'totalPages' => 1,
            'pageSize' => $size,
            'totalItems' => 0,
        ];

        if (is_wp_error($response) || !is_array($response)) {
            $this->get_logger()->warning('[OrgRoster] Group members response error', [
                'source' => 'wicket-orgroster',
                'error' => is_wp_error($response) ? $response->get_error_message() : 'invalid_response',
            ]);

            return [
                'members' => $members,
                'pagination' => $pagination,
                'query' => $query,
            ];
        }

        $included_lookup = $this->build_included_lookup($response['included'] ?? []);
        $data = $response['data'] ?? [];

        foreach ($data as $item) {
            $person_id = $item['relationships']['person']['data']['id'] ?? '';
            if (!$person_id) {
                continue;
            }

            $member_org_identifier = $this->extract_org_identifier($item, $org_identifier);
            if ('' !== $org_identifier && $member_org_identifier !== $org_identifier) {
                continue;
            }

            $person = $included_lookup['people'][$person_id] ?? null;
            $attributes = is_array($person) ? ($person['attributes'] ?? []) : [];
            $given = (string) ($attributes['given_name'] ?? '');
            $family = (string) ($attributes['family_name'] ?? '');
            $full_name = trim(trim($given) . ' ' . trim($family));

            $email = (string) ($attributes['email'] ?? '');
            if ('' === $email && isset($attributes['primary_email'])) {
                $email = (string) $attributes['primary_email'];
            }

            $members[] = [
                'group_member_id' => $item['id'] ?? '',
                'person_uuid' => $person_id,
                'full_name' => $full_name,
                'email' => $email,
                'role' => $item['attributes']['type'] ?? '',
                'custom_data_field' => $item['attributes']['custom_data_field'] ?? null,
            ];
        }

        $page_meta = $response['meta']['page'] ?? [];
        if (is_array($page_meta)) {
            $pagination['currentPage'] = (int) ($page_meta['number'] ?? $pagination['currentPage']);
            $pagination['totalPages'] = (int) ($page_meta['total_pages'] ?? $pagination['totalPages']);
            $pagination['pageSize'] = (int) ($page_meta['size'] ?? $pagination['pageSize']);
            $pagination['totalItems'] = (int) ($page_meta['total_items'] ?? count($members));
        } else {
            $pagination['totalItems'] = count($members);
        }

        $this->get_logger()->info('[OrgRoster] Group members normalized', [
            'source' => 'wicket-orgroster',
            'count' => count($members),
            'page' => $pagination['currentPage'],
            'total_pages' => $pagination['totalPages'],
        ]);

        return [
            'members' => $members,
            'pagination' => $pagination,
            'query' => $query,
        ];
    }

    /**
     * Create group membership with custom data.
     *
     * @param string $person_uuid
     * @param string $group_uuid
     * @param string $role_slug
     * @param array|null $custom_data_field
     * @return array|\WP_Error
     */
    public function create_group_member(string $person_uuid, string $group_uuid, string $role_slug, $custom_data_field = null)
    {
        if (!function_exists('wicket_api_client')) {
            return new \WP_Error('missing_client', 'MDP API client unavailable.');
        }

        $payload = [
            'data' => [
                'type' => 'group_members',
                'attributes' => [
                    'type' => $role_slug,
                    'start_date' => (new \DateTime('@' . strtotime(date('Y-m-d H:i:s', current_time('timestamp'))), wp_timezone()))->format('Y-m-d\T00:00:00P'),
                    'end_date' => null,
                    'custom_data_field' => $custom_data_field,
                    'person_id' => $person_uuid,
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'type' => 'groups',
                            'id' => $group_uuid,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $client = wicket_api_client();
            $this->get_logger()->info('[OrgRoster] Creating group member', [
                'source' => 'wicket-orgroster',
                'group_uuid' => $group_uuid,
                'person_uuid' => $person_uuid,
                'role' => $role_slug,
            ]);

            return $client->post('group_members', ['json' => $payload]);
        } catch (\Throwable $e) {
            return new \WP_Error('wicket_api_error', $e->getMessage());
        }
    }

    /**
     * End-date or delete group membership.
     *
     * @param string $group_member_id
     * @return array|\WP_Error
     */
    public function remove_group_member(string $group_member_id)
    {
        if (empty($group_member_id) || !function_exists('wicket_api_client')) {
            return new \WP_Error('missing_param', 'Group member id is required.');
        }

        $mode = (string) ($this->get_groups_config()['removal']['mode'] ?? 'end_date');
        if ('delete' === $mode && function_exists('wicket_remove_group_member')) {
            $deleted = wicket_remove_group_member($group_member_id);
            if ($deleted) {
                $this->get_logger()->info('[OrgRoster] Deleted group member', [
                    'source' => 'wicket-orgroster',
                    'group_member_id' => $group_member_id,
                ]);

                return ['status' => 'success'];
            }

            return new \WP_Error('delete_failed', 'Unable to delete group member.');
        }

        $format = (string) ($this->get_groups_config()['removal']['end_date_format'] ?? 'Y-m-d\T00:00:00P');
        $end_date = (new \DateTime('@' . strtotime(date('Y-m-d H:i:s', current_time('timestamp'))), wp_timezone()))->format($format);

        $payload = [
            'data' => [
                'type' => 'group_members',
                'id' => $group_member_id,
                'attributes' => [
                    'end_date' => $end_date,
                ],
            ],
        ];

        try {
            $client = wicket_api_client();
            $this->get_logger()->info('[OrgRoster] End-dating group member', [
                'source' => 'wicket-orgroster',
                'group_member_id' => $group_member_id,
                'end_date' => $end_date,
            ]);

            return $client->patch('group_members/' . rawurlencode($group_member_id), ['json' => $payload]);
        } catch (\Throwable $e) {
            return new \WP_Error('wicket_api_error', $e->getMessage());
        }
    }

    /**
     * Retrieve shared logger.
     *
     * @return \WC_Logger
     */
    private function get_logger()
    {
        if (null === $this->logger) {
            $this->logger = wc_get_logger();
        }

        return $this->logger;
    }
}
