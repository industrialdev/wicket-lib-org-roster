<?php

/**
 * Member Model for handling member data.
 */

namespace OrgManagement\Services;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles data operations for organization members.
 */
class MemberService
{
    /**
     * @var PermissionService|null
     */
    private $permissionService = null;

    /**
     * @var ConnectionService|null
     */
    private $connectionService = null;

    /**
     * @var MembershipService|null
     */
    private $membershipService = null;

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var RosterManagementStrategy[]
     */
    private $strategies = [];

    /**
     * @var array
     */
    private $config;

    /**
     * Constructor.
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
        $this->config = \OrgManagement\Config\OrgManConfig::get();
        $this->initStrategies();
    }

    /**
     * Helper method to get cached data if cache is enabled.
     *
     * @param string $cache_key The cache key.
     * @return mixed|false Cached data or false if not found/disabled.
     */
    private function getCachedData($cache_key)
    {
        return (new CacheService())->get($cache_key);
    }

    /**
     * Helper method to set cached data if cache is enabled.
     *
     * @param string $cache_key The cache key.
     * @param mixed $data The data to cache.
     * @return void
     */
    private function setCachedData($cache_key, $data)
    {
        (new CacheService())->set($cache_key, $data);
    }

    /**
     * Initialize the available strategies.
     */
    private function initStrategies()
    {
        $this->strategies['cascade'] = new Strategies\CascadeStrategy();
        $this->strategies['direct'] = new Strategies\DirectAssignmentStrategy();
        $this->strategies['groups'] = new Strategies\GroupsStrategy();
        $this->strategies['membership_cycle'] = new Strategies\MembershipCycleStrategy();
    }

    /**
     * Get the current roster management strategy.
     *
     * @return RosterManagementStrategy
     */
    private function getStrategy()
    {
        $mode = $this->configService->getRosterMode();

        return $this->strategies[$mode] ?? $this->strategies['cascade'];
    }

    /**
     * Add a member to an organization.
     *
     * @param string $org_id The organization ID.
     * @param array  $member_data Data for the new member.
     * @return array|\WP_Error
     */
    public function addMember($org_id, $member_data, $context = [])
    {
        return $this->getStrategy()->addMember($org_id, $member_data, $context);
    }

    /**
     * Remove a member from an organization.
     *
     * @param string $org_id The organization ID.
     * @param string $person_uuid The UUID of the person to remove.
     * @param array  $context Additional context for the operation.
     * @return array|\WP_Error
     */
    public function removeMember($org_id, $person_uuid, $context = [])
    {
        return $this->getStrategy()->removeMember($org_id, $person_uuid, $context);
    }

    /**
     * Check if a user has the required roles within an organization.
     *
     * @param string $person_uuid The user's UUID.
     * @param string $org_id The organization ID.
     * @param array  $roles The roles to check for.
     * @return bool True if the user has at least one of the roles, false otherwise.
     */
    public function hasRole($person_uuid, $org_id, $roles)
    {
        return $this->personHasOrgRoles($person_uuid, $roles, $org_id, false);
    }

    /**
     * Check if a person has specific roles within an organization.
     *
     * @param string       $person_uuid The UUID of the person
     * @param array|string $roles The roles to check. Can be a string or an array of roles.
     * @param string       $org_id The organization ID
     * @param bool         $all_true Default: false. If true, all roles must be in the user's roles. If false, at least one role must be in the user's roles.
     * @return bool True if condition met, false if not.
     */
    public function personHasOrgRoles($person_uuid, $roles, $org_id, $all_true = false)
    {
        if (empty($person_uuid) || empty($roles) || empty($org_id)) {
            return false;
        }

        // Get current person roles for the organization
        $current_roles = $this->permissionService()->getPersonCurrentRolesByOrgId($person_uuid, $org_id);

        if (!is_array($current_roles) || empty($current_roles)) {
            return false;
        }

        // Normalize roles to array
        if (!is_array($roles)) {
            if (str_contains($roles, ',')) {
                $roles = explode(',', $roles);
            } else {
                $roles = [$roles];
            }
        }

        // Sanitize roles
        $roles = array_map('sanitize_key', array_filter($roles));

        if (empty($roles)) {
            return false;
        }

        if ($all_true) {
            // All roles must be present
            foreach ($roles as $role) {
                if (!in_array($role, $current_roles, true)) {
                    return false;
                }
            }

            return true;
        } else {
            // At least one role must be present
            foreach ($roles as $role) {
                if (in_array($role, $current_roles, true)) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Lazily instantiate PermissionService.
     *
     * @return PermissionService
     */
    private function permissionService(): PermissionService
    {
        if (!isset($this->permissionService)) {
            $this->permissionService = new PermissionService();
        }

        return $this->permissionService;
    }

    /**
     * Lazily instantiate ConnectionService.
     *
     * @return ConnectionService
     */
    private function connectionService(): ConnectionService
    {
        if (!isset($this->connectionService)) {
            $this->connectionService = new ConnectionService();
        }

        return $this->connectionService;
    }

    /**
     * Lazily instantiate MembershipService.
     *
     * @return MembershipService
     */
    private function membershipService(): MembershipService
    {
        if (!isset($this->membershipService)) {
            $this->membershipService = new MembershipService();
        }

        return $this->membershipService;
    }

    /**
     * Retrieve organization membership members via legacy helper.
     *
     * @param string $membershipUuid Membership identifier.
     * @param array  $args           Optional arguments (page, size, query).
     * @return array|null
     */
    public function getMembershipMembers(string $membershipUuid, array $args = []): ?array
    {
        if (empty($membershipUuid)) {
            return null;
        }

        $defaultPageSize = 15;

        $page = max(1, (int) ($args['page'] ?? 1));
        $size = max(1, (int) ($args['size'] ?? $defaultPageSize));
        $searchTerm = isset($args['query']) ? sanitize_text_field((string) $args['query']) : '';
        $isLazy = (bool) ($args['lazy'] ?? false);

        $logger = \Wicket()->log();

        // Cache initial load only (no search term)
        if (empty($searchTerm)) {
            $cache_key = 'orgman_members_' . md5($membershipUuid . $page . $size . (int) $isLazy);
            $cached_data = $this->getCachedData($cache_key);

            if (false !== $cached_data) {
                return $cached_data;
            }
        }

        if ('' !== $searchTerm) {
            try {
                $searchResult = $this->membershipService()->membershipSearchMembers(
                    $membershipUuid,
                    [
                        'page'  => $page,
                        'size'  => $size,
                        'query' => $searchTerm,
                    ]
                );

                if (!is_wp_error($searchResult) && is_array($searchResult)) {
                    $searchData = $searchResult['data'] ?? null;
                    if (is_array($searchData) && !empty($searchData)) {
                        return $searchResult;
                    }
                }

                if (is_wp_error($searchResult)) {
                }
            } catch (\Throwable $searchException) {
                $logger->error(
                    '[OrgMan] MembershipService member search threw exception: ' . $searchException->getMessage(),
                    [
                        'source'          => 'wicket-orgman',
                        'membership_uuid' => $membershipUuid,
                        'query'           => $searchTerm,
                    ]
                );
            }
        }

        if ('' !== $searchTerm && function_exists('wicket_api_client')) {
            $queryArgs = [
                'page[number]' => $page,
                'page[size]'   => $size,
                'include'      => 'person,emails,phones',
            ];

            $payload = [
                'filter' => [
                    'organization_membership_uuid_in'                    => [$membershipUuid],
                    'person_full_name_or_person_emails_address_cont'     => $searchTerm,
                    'active_at'                                          => 'now',
                ],
            ];

            try {
                $client = wicket_api_client();
                $response = $client->post(
                    '/person_memberships/query?' . http_build_query($queryArgs),
                    ['json' => $payload]
                );

                $normalized = $this->normalizeMembershipResponse($response);
                if (null !== $normalized) {
                    return $normalized;
                }
            } catch (\Throwable $searchException) {
                $logger->error(
                    '[OrgMan] person_memberships query search failed: ' . $searchException->getMessage(),
                    [
                        'source'          => 'wicket-orgman',
                        'membership_uuid' => $membershipUuid,
                        'query'           => $searchTerm,
                    ]
                );
            }
        }

        $queryParams = [
            'page[number]' => $page,
            'page[size]'   => $size,
            'include'      => 'person,membership,user',
            'filter[active_at]' => 'now',
        ];

        if ('' !== $searchTerm) {
            $queryParams['filter[search]'] = $searchTerm;
        }

        if (function_exists('wicket_api_client')) {
            try {
                $client = wicket_api_client();
                $endpoint = '/organization_memberships/' . rawurlencode($membershipUuid) . '/person_memberships';
                $response = $client->get($endpoint . '?' . http_build_query($queryParams));

                $normalized = $this->normalizeMembershipResponse($response);
                if (null !== $normalized) {
                    // Cache initial load only (no search term)
                    if (empty($searchTerm)) {
                        $isLazy = (bool) ($args['lazy'] ?? false);
                        $cache_key = 'orgman_members_' . md5($membershipUuid . $page . $size . (int) $isLazy);
                        $this->setCachedData($cache_key, $normalized);
                    }

                    return $normalized;
                }

            } catch (\Throwable $e) {
                $logger->error(
                    '[OrgMan] Error fetching organization membership members: ' . $e->getMessage(),
                    [
                        'source'          => 'wicket-orgman',
                        'membership_uuid' => $membershipUuid,
                    ]
                );
            }
        }

        if ('' !== $searchTerm && function_exists('wicket_api_client')) {
            try {
                $client = wicket_api_client();
                $endpoint = '/organization_memberships/' . rawurlencode($membershipUuid) . '/person_memberships';
                $fallbackResponse = $client->get($endpoint . '?' . http_build_query([
                    'page[number]' => 1,
                    'page[size]' => max(100, $size),
                    'filter[active_at]' => 'now',
                    'include' => 'person,emails,phones',
                ]));

                $normalizedFallback = $this->normalizeMembershipResponse($fallbackResponse);
                if (is_array($normalizedFallback) && isset($normalizedFallback['data']) && is_array($normalizedFallback['data'])) {
                    $locallyFiltered = $this->filterMembershipResponseByQuery($normalizedFallback, $searchTerm);

                    return $locallyFiltered;
                }
            } catch (\Throwable $localFallbackException) {
                $logger->error(
                    '[OrgMan] Local search fallback failed: ' . $localFallbackException->getMessage(),
                    [
                        'source' => 'wicket-orgman',
                        'membership_uuid' => $membershipUuid,
                        'query' => $searchTerm,
                    ]
                );
            }
        }

        $response = $this->membershipService()->getOrgMembershipMembers($membershipUuid, $args);
        if (is_wp_error($response)) {
            /** @var \WP_Error $response */
            $error_message = $response->get_error_message();
            \Wicket()->log()->error(
                '[OrgMan] MembershipService::getOrgMembershipMembers() returned error',
                [
                    'source'          => 'wicket-orgman',
                    'membership_uuid' => $membershipUuid,
                    'error'           => $error_message,
                ]
            );

            return null;
        }
        $final_response = $this->normalizeMembershipResponse($response);

        // Cache initial load only (no search term)
        if (empty($searchTerm) && null !== $final_response) {
            $isLazy = (bool) ($args['lazy'] ?? false);
            $cache_key = 'orgman_members_' . md5($membershipUuid . $page . $size . (int) $isLazy);
            $this->setCachedData($cache_key, $final_response);
        }

        return $final_response;
    }

    /**
     * Filter a normalized membership response by search term using person name/email fields.
     *
     * @param array $response
     * @param string $query
     * @return array
     */
    private function filterMembershipResponseByQuery(array $response, string $query): array
    {
        $query = strtolower(trim($query));
        if ($query === '') {
            return $response;
        }

        $peopleIndex = [];
        $emailsByPerson = [];
        $included = is_array($response['included'] ?? null) ? $response['included'] : [];

        foreach ($included as $item) {
            $type = (string) ($item['type'] ?? '');
            $id = (string) ($item['id'] ?? '');
            if ($type === 'people' && $id !== '') {
                $peopleIndex[$id] = $item;
                continue;
            }

            if ($type === 'emails') {
                $personId = (string) ($item['relationships']['person']['data']['id'] ?? '');
                $emailAddress = (string) ($item['attributes']['address'] ?? '');
                if ($personId !== '' && $emailAddress !== '') {
                    if (!isset($emailsByPerson[$personId])) {
                        $emailsByPerson[$personId] = [];
                    }
                    $emailsByPerson[$personId][] = $emailAddress;
                }
            }
        }

        $filteredData = [];
        $sourceData = is_array($response['data'] ?? null) ? $response['data'] : [];
        foreach ($sourceData as $membershipRow) {
            $personId = (string) ($membershipRow['relationships']['person']['data']['id'] ?? '');
            $personAttrs = is_array($peopleIndex[$personId]['attributes'] ?? null) ? $peopleIndex[$personId]['attributes'] : [];

            $parts = [];
            $parts[] = (string) ($personAttrs['full_name'] ?? '');
            $parts[] = trim((string) ($personAttrs['first_name'] ?? '') . ' ' . (string) ($personAttrs['last_name'] ?? ''));
            $parts[] = (string) ($personAttrs['name'] ?? '');

            if (isset($emailsByPerson[$personId]) && is_array($emailsByPerson[$personId])) {
                foreach ($emailsByPerson[$personId] as $emailAddress) {
                    $parts[] = (string) $emailAddress;
                }
            }

            $haystack = strtolower(implode(' ', array_filter($parts, static function ($value) {
                return is_string($value) && $value !== '';
            })));

            if ($haystack !== '' && str_contains($haystack, $query)) {
                $filteredData[] = $membershipRow;
            }
        }

        $response['data'] = $filteredData;
        if (isset($response['meta']['page']) && is_array($response['meta']['page'])) {
            $response['meta']['page']['total_items'] = count($filteredData);
            $response['meta']['page']['total_pages'] = 1;
            $response['meta']['page']['number'] = 1;
        }

        return $response;
    }

    /**
     * Normalize relationship type slug for matching/filtering.
     *
     * @param string $type Raw relationship type value.
     * @return string
     */
    private function normalizeRelationshipType(string $type): string
    {
        $normalized = strtolower(trim($type));
        if ($normalized === '') {
            return '';
        }

        $normalized = str_replace(['-', ' '], '_', $normalized);
        $normalized = (string) preg_replace('/_+/', '_', $normalized);
        $normalized = sanitize_key($normalized);

        $aliases = [
            'affiliation' => 'affiliate',
            'affiliated' => 'affiliate',
            'affiliation_relationship' => 'affiliate',
            'companyadmin' => 'company_admin',
            'companyadministrator' => 'company_admin',
            'regularmember' => 'regular_member',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    /**
     * Normalize a relationship-type list into unique slugs.
     *
     * @param array $types
     * @return array
     */
    private function normalizeRelationshipTypeList(array $types): array
    {
        $normalized = [];
        foreach ($types as $type) {
            $slug = $this->normalizeRelationshipType((string) $type);
            if ($slug === '') {
                continue;
            }
            $normalized[] = $slug;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Resolve a human-readable label for a relationship slug.
     *
     * @param string $slug
     * @param array  $labels
     * @return string
     */
    private function resolveRelationshipLabel(string $slug, array $labels): string
    {
        if (isset($labels[$slug]) && is_string($labels[$slug]) && trim($labels[$slug]) !== '') {
            return trim($labels[$slug]);
        }

        return ucwords(str_replace('_', ' ', $slug));
    }

    /**
     * Normalize role value into canonical slug for filtering/display.
     *
     * @param string $role
     * @return string
     */
    private function normalizeRoleSlugValue(string $role): string
    {
        $normalized = strtolower(trim($role));
        if ($normalized === '') {
            return '';
        }

        $normalized = str_replace(['-', ' '], '_', $normalized);
        $normalized = (string) preg_replace('/_+/', '_', $normalized);

        return sanitize_key($normalized);
    }

    /**
     * Resolve role slug aliases from config.
     *
     * @return array<string, string>
     */
    private function getRoleSlugAliases(): array
    {
        $configuredAliases = (array) ($this->config['access']['roles']['aliases'] ?? []);

        $aliases = [];
        foreach ($configuredAliases as $sourceRole => $targetRole) {
            $sourceSlug = $this->normalizeRoleSlugValue((string) $sourceRole);
            $targetSlug = $this->normalizeRoleSlugValue((string) $targetRole);

            if ($sourceSlug === '' || $targetSlug === '') {
                continue;
            }

            $aliases[$sourceSlug] = $targetSlug;
        }

        return $aliases;
    }

    /**
     * Normalize role value into canonical slug for filtering/display.
     *
     * @param string $role
     * @return string
     */
    private function normalizeRoleSlug(string $role): string
    {
        $normalized = $this->normalizeRoleSlugValue($role);
        if ($normalized === '') {
            return '';
        }

        $aliases = $this->getRoleSlugAliases();

        return $aliases[$normalized] ?? $normalized;
    }

    /**
     * Normalize role-list values into canonical slugs.
     *
     * @param array $roles
     * @return array
     */
    private function normalizeRoleList(array $roles): array
    {
        $normalized = [];
        foreach ($roles as $role) {
            $slug = $this->normalizeRoleSlug((string) $role);
            if ($slug === '') {
                continue;
            }
            $normalized[] = $slug;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Filter role list by allowlist/excludelist and normalize output slugs.
     *
     * @param array $roles
     * @param array $allowlist
     * @param array $excludes
     * @return array
     */
    private function filterDisplayRoles(array $roles, array $allowlist = [], array $excludes = []): array
    {
        $allowLookup = !empty($allowlist) ? array_fill_keys($allowlist, true) : [];
        $excludeLookup = !empty($excludes) ? array_fill_keys($excludes, true) : [];

        $filtered = [];
        foreach ($roles as $role) {
            $slug = $this->normalizeRoleSlug((string) $role);
            if ($slug === '') {
                continue;
            }

            if (!empty($allowLookup) && !isset($allowLookup[$slug])) {
                continue;
            }

            if (!empty($excludeLookup) && isset($excludeLookup[$slug])) {
                continue;
            }

            $filtered[] = $slug;
        }

        return array_values(array_unique($filtered));
    }

    /**
     * Merge duplicate prepared member rows for the same person.
     *
     * @param array $existing
     * @param array $incoming
     * @return array
     */
    private function mergePreparedMemberRows(array $existing, array $incoming): array
    {
        $existing['roles'] = array_values(array_unique(array_merge(
            (array) ($existing['roles'] ?? []),
            (array) ($incoming['roles'] ?? [])
        )));

        $existing['current_roles'] = array_values(array_unique(array_merge(
            (array) ($existing['current_roles'] ?? []),
            (array) ($incoming['current_roles'] ?? [])
        )));

        if (empty($existing['current_roles']) && !empty($existing['roles'])) {
            $existing['current_roles'] = (array) $existing['roles'];
        }

        $existing['relationship_names_list'] = array_values(array_unique(array_merge(
            (array) ($existing['relationship_names_list'] ?? []),
            (array) ($incoming['relationship_names_list'] ?? [])
        )));

        $existing['relationship_slugs'] = array_values(array_unique(array_merge(
            (array) ($existing['relationship_slugs'] ?? []),
            (array) ($incoming['relationship_slugs'] ?? [])
        )));

        $existing['person_connection_ids_list'] = array_values(array_unique(array_merge(
            (array) ($existing['person_connection_ids_list'] ?? []),
            (array) ($incoming['person_connection_ids_list'] ?? [])
        )));

        if (empty($existing['relationship_description']) && !empty($incoming['relationship_description'])) {
            $existing['relationship_description'] = $incoming['relationship_description'];
        }

        if (empty($existing['person_membership_id']) && !empty($incoming['person_membership_id'])) {
            $existing['person_membership_id'] = $incoming['person_membership_id'];
        }

        if (!empty($incoming['is_owner'])) {
            $existing['is_owner'] = true;
        }

        foreach (['first_name', 'last_name', 'full_name', 'title', 'email', 'status', 'job_level', 'confirmed_at'] as $field) {
            if (empty($existing[$field]) && !empty($incoming[$field])) {
                $existing[$field] = $incoming[$field];
            }
        }

        return $existing;
    }

    /**
     * Convert internal prepared row arrays to template payload shape.
     *
     * @param array $memberRow
     * @return array
     */
    private function finalizePreparedMemberRow(array $memberRow): array
    {
        $relationshipNames = array_values(array_filter((array) ($memberRow['relationship_names_list'] ?? []), static function ($value): bool {
            return is_string($value) && trim($value) !== '';
        }));
        $relationshipSlugs = array_values(array_filter((array) ($memberRow['relationship_slugs'] ?? []), static function ($value): bool {
            return is_string($value) && trim($value) !== '';
        }));
        $personConnectionIds = array_values(array_filter((array) ($memberRow['person_connection_ids_list'] ?? []), static function ($value): bool {
            return is_string($value) && trim($value) !== '';
        }));

        $memberRow['relationship_names'] = !empty($relationshipNames) ? implode(', ', $relationshipNames) : null;
        $memberRow['relationship_type'] = !empty($relationshipSlugs) ? reset($relationshipSlugs) : null;
        $memberRow['person_connection_ids'] = !empty($personConnectionIds) ? implode(',', $personConnectionIds) : null;

        unset($memberRow['relationship_names_list'], $memberRow['relationship_slugs'], $memberRow['person_connection_ids_list']);

        return $memberRow;
    }

    /**
     * Clear the cached member list for a specific organization membership.
     *
     * @param string $membershipUuid The membership UUID.
     * @return void
     */
    public function clearMembersCache(string $membershipUuid): void
    {
        (new CacheService())->invalidateMemberCache($membershipUuid);
    }

    /**
     * Retrieve formatted member data with pagination metadata.
     *
     * @param string $membershipUuid Organization membership identifier.
     * @param string $orgUuid        Organization identifier.
     * @param array  $args           Optional arguments (page, size, query).
     * @return array{
     *     members: array<int, array>,
     *     pagination: array<string, int>,
     *     org_uuid: string,
     *     query: string
     * }
     */
    public function getMembers(string $membershipUuid, string $orgUuid, array $args = [], bool $lazy = false): array
    {
        $page = max(1, (int) ($args['page'] ?? 1));
        $size = max(1, (int) ($args['size'] ?? 15));
        $query = isset($args['query']) ? sanitize_text_field((string) $args['query']) : '';

        $membersResponse = $this->getMembershipMembers(
            $membershipUuid,
            [
                'page'  => $page,
                'size'  => $size,
                'query' => $query ?: null,
                'lazy'  => $lazy,
            ]
        );

        return $this->prepareMembersResult(
            $membersResponse,
            [
                'org_uuid'        => $orgUuid,
                'membership_uuid' => $membershipUuid,
                'page'            => $page,
                'size'            => $size,
                'query'           => $query,
                'lazy'            => $lazy,
            ]
        );
    }

    /**
     * Fetch a single member's full data by person UUID via direct API query.
     * Used by the lazy-load SSE endpoint to avoid text-search limitations.
     *
     * @param string $personUuid     Person UUID to look up.
     * @param string $membershipUuid Organization membership UUID.
     * @param string $orgUuid        Organization UUID.
     * @return array|null Normalized member array, or null if not found.
     */
    public function getMemberByPersonUuid(string $personUuid, string $membershipUuid, string $orgUuid): ?array
    {
        if (!function_exists('wicket_api_client')) {
            return null;
        }

        try {
            $client = wicket_api_client();

            // Use the same nested endpoint pattern as getMembershipMembers()
            // GET /organization_memberships/{membershipUuid}/person_memberships
            $endpoint = '/organization_memberships/' . rawurlencode($membershipUuid) . '/person_memberships';
            $queryParams = [
                'page[number]' => 1,
                'page[size]'   => 100,
                'filter[active_at]' => 'now',
                'include'      => 'person,membership,user',
            ];

            $response = $client->get($endpoint . '?' . http_build_query($queryParams));
        } catch (\Throwable $e) {
            \Wicket()->log()->error(
                '[OrgMan] getMemberByPersonUuid API call failed',
                [
                    'source'          => 'wicket-orgman',
                    'person_uuid'     => $personUuid,
                    'membership_uuid' => $membershipUuid,
                    'org_uuid'        => $orgUuid,
                    'error'           => $e->getMessage(),
                ]
            );

            return null;
        }

        if (!is_array($response) || empty($response['data'])) {
            \Wicket()->log()->warning('[OrgMan] getMemberByPersonUuid: No data returned from API', [
                'source'          => 'wicket-orgman',
                'person_uuid'     => $personUuid,
                'membership_uuid' => $membershipUuid,
                'org_uuid'        => $orgUuid,
                'response_keys'   => is_array($response) ? array_keys($response) : 'not_array',
            ]);
            return null;
        }

        // Filter results to find the matching person by person_id
        $members = $response['data'] ?? [];
        $matched_member = null;

        \Wicket()->log()->debug('[OrgMan] getMemberByPersonUuid: Filtering results', [
            'source'          => 'wicket-orgman',
            'person_uuid'     => $personUuid,
            'membership_uuid' => $membershipUuid,
            'org_uuid'        => $orgUuid,
            'results_count'   => count($members),
        ]);

        foreach ($members as $idx => $member) {
            $person_id = $member['relationships']['person']['data']['id'] ?? null;

            \Wicket()->log()->debug('[OrgMan] getMemberByPersonUuid: Checking member', [
                'source'            => 'wicket-orgman',
                'person_uuid'       => $personUuid,
                'index'             => $idx,
                'person_id'         => $person_id,
                'target_person_uuid'=> $personUuid,
                'match'             => $person_id === $personUuid ? 'YES' : 'NO',
            ]);

            if ($person_id === $personUuid) {
                $matched_member = $member;
                break;
            }
        }

        if (!$matched_member) {
            \Wicket()->log()->warning('[OrgMan] getMemberByPersonUuid: No matching person found', [
                'source'          => 'wicket-orgman',
                'person_uuid'     => $personUuid,
                'membership_uuid' => $membershipUuid,
                'org_uuid'        => $orgUuid,
                'total_results'   => count($members),
            ]);
            return null;
        }

        // Reconstruct response with just the matched member
        $filtered_response = $response;
        $filtered_response['data'] = [$matched_member];

        \Wicket()->log()->warning('[OrgMan] getMemberByPersonUuid: Response structure', [
            'source' => 'wicket-orgman',
            'person_uuid' => $personUuid,
            'has_included' => isset($response['included']),
            'included_count' => isset($response['included']) ? count($response['included']) : 0,
            'included_types' => isset($response['included']) ? array_map(fn($item) => $item['type'] ?? 'unknown', $response['included']) : [],
        ]);

        $result = $this->prepareMembersResult(
            $filtered_response,
            [
                'org_uuid'        => $orgUuid,
                'membership_uuid' => $membershipUuid,
                'page'            => 1,
                'size'            => 1,
                'query'           => '',
                'lazy'            => false,
            ]
        );

        return $result['members'][0] ?? null;
    }

    /**
     * Retrieve group roster members for groups strategy.
     *
     * @param string $group_uuid Group identifier.
     * @param string $org_identifier Organization identifier for filtering.
     * @param array $args Optional args (page, size, query).
     * @return array
     */
    public function getGroupMembers(string $group_uuid, string $org_identifier, array $args = []): array
    {
        $page = max(1, (int) ($args['page'] ?? 1));
        $size = max(1, (int) ($args['size'] ?? 15));
        $query = isset($args['query']) ? sanitize_text_field((string) $args['query']) : '';
        $org_uuid = isset($args['org_uuid']) ? sanitize_text_field((string) $args['org_uuid']) : '';

        $group_service = new GroupService();

        return $group_service->getGroupMembers($group_uuid, $org_identifier, [
            'page' => $page,
            'size' => $size,
            'query' => $query,
            'org_uuid' => $org_uuid,
        ]);
    }

    /**
     * Search members with pagination support.
     *
     * @param string $membershipUuid Organization membership identifier.
     * @param string $orgUuid        Organization identifier.
     * @param string $search         Search term.
     * @param array  $args           Optional arguments (page, size).
     * @return array
     */
    public function searchMembers(string $membershipUuid, string $orgUuid, string $search, array $args = []): array
    {
        $args['query'] = $search;

        return $this->getMembers($membershipUuid, $orgUuid, $args);
    }

    /**
     * Structure the members response for templates and partials.
     *
     * @param array|null $membersResponse Raw response from API/helpers.
     * @param array      $context         Context values: org_uuid, membership_uuid, page, size, query.
     * @return array
     */
    private function prepareMembersResult(?array $membersResponse, array $context): array
    {
        $logger = \Wicket()->log();

        $page = max(1, (int) ($context['page'] ?? 1));
        $size = max(1, (int) ($context['size'] ?? 15));
        $query = isset($context['query']) ? (string) $context['query'] : '';
        $orgUuid = (string) ($context['org_uuid'] ?? '');
        $membershipUuid = $context['membership_uuid'] ?? null;
        $isLazy = (bool) ($context['lazy'] ?? false);

        $rawMembers = [];
        if (is_array($membersResponse)) {
            if (isset($membersResponse['data']) && is_array($membersResponse['data'])) {
                $rawMembers = $membersResponse['data'];
            } elseif (isset($membersResponse[0])) {
                $rawMembers = $membersResponse;
            }
        }

        $logger->debug('[OrgMan] prepareMembersResult input', [
            'source' => 'wicket-orgman',
            'raw_members_count' => count($rawMembers),
            'page' => $page,
            'size' => $size,
            'isLazy' => $isLazy,
            'response_has_data' => isset($membersResponse['data']),
            'response_has_included' => isset($membersResponse['included']),
        ]);

        // Convert any stdClass objects in rawMembers to arrays
        $rawMembers = array_map(static function ($member) {
            if (is_object($member) && !is_array($member)) {
                return json_decode(json_encode($member), true);
            }

            return $member;
        }, $rawMembers);

        $ownerId = null;
        if (!empty($membershipUuid)) {
            try {
                $membershipService = new MembershipService();
                $membershipData = $membershipService->getOrgMembershipData((string) $membershipUuid);
                if (is_array($membershipData)) {
                    $ownerId = $membershipData['data']['relationships']['owner']['data']['id'] ?? null;
                }
            } catch (\Throwable $e) {
                $logger->warning(
                    '[OrgMan] Failed to resolve membership owner: ' . $e->getMessage(),
                    [
                        'source'          => 'wicket-orgman',
                        'membership_uuid' => $membershipUuid,
                    ]
                );
            }
        }

        $peopleIndex = [];
        $userIndex = [];
        if (is_array($membersResponse) && isset($membersResponse['included']) && is_array($membersResponse['included'])) {
            foreach ($membersResponse['included'] as $included) {
                // Convert stdClass objects to arrays
                if (is_object($included) && !is_array($included)) {
                    $included = json_decode(json_encode($included), true);
                }

                $type = $included['type'] ?? '';
                $id = $included['id'] ?? '';

                if ($type === 'people' && $id !== '') {
                    $peopleIndex[$id] = $included;
                }

                // Build user index by person_id (user has relationship to person)
                if ($type === 'users' && $id !== '') {
                    $personId = $included['relationships']['person']['data']['id'] ?? '';
                    if ($personId !== '') {
                        $userIndex[$personId] = $included;
                    }
                }
            }
        }

        \Wicket()->log()->warning('[OrgMan] userIndex built in prepareMembersResult', [
            'userIndex_count' => count($userIndex),
            'userIndex_keys' => array_keys($userIndex),
        ]);

        $allowedTypes = $this->normalizeRelationshipTypeList((array) ($this->config['relationships']['filters']['allowlist'] ?? []));
        $excludedTypes = $this->normalizeRelationshipTypeList((array) ($this->config['relationships']['filters']['denylist'] ?? []));
        $displayRoleAllowlist = $this->normalizeRoleList((array) ($this->config['presentation']['member_list']['display_roles']['allowlist'] ?? []));
        $displayRoleExcludes = $this->normalizeRoleList((array) ($this->config['presentation']['member_list']['display_roles']['denylist'] ?? []));
        $relationshipTypeLabels = (array) ($this->config['relationships']['labels']['custom'] ?? []);

        $members = [];
        $membersWithoutPerson = [];

        $loopCounter = 0;
        $loopContinue = 0;
        $loopSuccess = 0;

        // Pre-fetch connections and roles for all unique people to avoid N+1 calls inside the loop.
        $connectionsByPerson = [];
        $rolesByPerson = [];
        if (!$isLazy && !empty($rawMembers) && !empty($orgUuid) && function_exists('wicket_api_client')) {
            $uniquePersonIds = [];
            foreach ($rawMembers as $member) {
                $personId = $member['relationships']['person']['data']['id']
                    ?? $member['person']['id']
                    ?? null;
                if ($personId && !in_array($personId, $uniquePersonIds, true)) {
                    $uniquePersonIds[] = $personId;
                }
            }

            if (!empty($uniquePersonIds)) {
                $client = wicket_api_client();
                foreach ($uniquePersonIds as $personId) {
                    // Fetch connections
                    try {
                        $endpoint = 'people/' . rawurlencode((string) $personId) . '/connections';
                        $params = [
                            'filter[connection_type_eq]' => 'all',
                            'sort' => '-created_at',
                        ];
                        $response = $client->get($endpoint, $params);

                        if (is_array($response) && isset($response['data']) && is_array($response['data'])) {
                            // Filter connections to this organization only
                            $orgConnections = array_filter($response['data'], static function ($conn) use ($orgUuid) {
                                $connOrgId = $conn['relationships']['organization']['data']['id'] ?? '';

                                return trim((string) $connOrgId) === trim((string) $orgUuid);
                            });

                            if (!empty($orgConnections)) {
                                $connectionsByPerson[$personId] = array_values($orgConnections);
                            } else {
                                $connectionsByPerson[$personId] = [];
                            }
                        }
                    } catch (\Throwable $e) {
                        $logger->warning('[OrgMan] Failed to pre-fetch connections', ['person_id' => $personId, 'error' => $e->getMessage()]);
                    }

                    // Fetch roles for MDP permission roles merging
                    try {
                        $roleParams = [
                            'page' => ['number' => 1, 'size' => 100],
                            'include' => 'resource',
                            'sort' => '-global,name',
                        ];
                        $roleResponse = $client->get('/people/' . rawurlencode((string) $personId) . '/roles', $roleParams);

                        if (is_array($roleResponse) && isset($roleResponse['data']) && is_array($roleResponse['data'])) {
                            $orgRoles = [];
                            foreach ($roleResponse['data'] as $role) {
                                $resourceId = $role['relationships']['resource']['data']['id'] ?? '';
                                if ((string) $resourceId === (string) $orgUuid) {
                                    $roleSlug = $role['attributes']['slug'] ?? '';
                                    if ($roleSlug !== '') {
                                        $orgRoles[] = $roleSlug;
                                    }
                                }
                            }
                            $rolesByPerson[$personId] = $orgRoles;
                        }
                    } catch (\Throwable $e) {
                        $logger->warning('[OrgMan] Failed to pre-fetch roles', ['person_id' => $personId, 'error' => $e->getMessage()]);
                    }
                }
            }
        }

        foreach ($rawMembers as $idx => $member) {
            $loopCounter++;

            // Convert stdClass objects to arrays
            if (is_object($member) && !is_array($member)) {
                $member = json_decode(json_encode($member), true);
            }

            if (!is_array($member)) {
                $logger->debug('[OrgMan] Skipping member: not an array', [
                    'source' => 'wicket-orgman',
                    'index' => $idx,
                    'member_type' => gettype($member),
                ]);
                $loopContinue++;
                continue;
            }

            $memberAttributes = $member['attributes'] ?? [];

            $personUuid = $member['relationships']['person']['data']['id']
                ?? $member['person']['id']
                ?? null;

            $personData = ($personUuid && isset($peopleIndex[$personUuid])) ? $peopleIndex[$personUuid] : null;
            $personAttributes = $personData['attributes'] ?? [];

            if (!$personData && $personUuid) {
                try {
                    $person = $this->getPersonById($personUuid);
                    if (is_array($person) && isset($person['data']['attributes'])) {
                        $personData = $person;
                        $personAttributes = $person['data']['attributes'];
                    }
                } catch (\Throwable $e) {
                    $logger->warning(
                        '[OrgMan] Failed to fetch person by id',
                        [
                            'source'    => 'wicket-orgman',
                            'person_id' => $personUuid,
                            'error'     => $e->getMessage(),
                        ]
                    );
                }
            }

            $currentRolesList = [];
            if ($personUuid && !$isLazy) {
                try {
                    // Use pre-fetched roles if available, fallback for safety
                    if (isset($rolesByPerson[$personUuid])) {
                        $rawRoles = $rolesByPerson[$personUuid];
                        $rolesList = $this->normalizeRoleList($rawRoles);
                    } else {
                        $rolesList = $this->getPersonCurrentRolesByOrgId($personUuid, $orgUuid);
                    }

                    if (is_array($rolesList)) {
                        $currentRolesList = array_values(array_filter(array_map('strval', $rolesList)));
                    }
                } catch (\Throwable $e) {
                    $logger->warning(
                        '[OrgMan] Failed to process person current roles',
                        [
                            'source'    => 'wicket-orgman',
                            'person_id' => $personUuid,
                            'org_id'    => $orgUuid,
                            'error'     => $e->getMessage(),
                        ]
                    );
                }
            }
            $currentRolesList = $this->filterDisplayRoles($currentRolesList, $displayRoleAllowlist, $displayRoleExcludes);

            $firstName = $personAttributes['given_name']
                ?? $personAttributes['first_name']
                ?? $memberAttributes['person_first_name']
                ?? $memberAttributes['first_name']
                ?? '';

            $lastName = $personAttributes['family_name']
                ?? $personAttributes['last_name']
                ?? $memberAttributes['person_last_name']
                ?? $memberAttributes['last_name']
                ?? '';

            $email = $personAttributes['primary_email_address']
                ?? $personAttributes['email']
                ?? $memberAttributes['person_email']
                ?? $memberAttributes['email']
                ?? '';

            $title = $personAttributes['job_title']
                ?? $memberAttributes['person_title']
                ?? $memberAttributes['title']
                ?? '';

            $roles = [];
            if (!empty($memberAttributes['roles']) && is_array($memberAttributes['roles'])) {
                $roles = array_filter(array_map('strval', $memberAttributes['roles']));
            } elseif (!empty($memberAttributes['type'])) {
                $roles = [str_replace('_', ' ', (string) $memberAttributes['type'])];
            }
            $roles = $this->filterDisplayRoles($roles, $displayRoleAllowlist, $displayRoleExcludes);

            $relationshipSlugs = [];
            $relationshipNamesBySlug = [];
            $relationshipDescription = null;
            $personConnectionIds = []; // Store all connection IDs for this organization
            if ($personUuid && !$isLazy) {
                try {
                    // Use pre-fetched data if available, fallback for safety
                    if (isset($connectionsByPerson[$personUuid])) {
                        $connectionsData = $connectionsByPerson[$personUuid];
                    } else {
                        $connections = $this->connectionService()->getPersonConnectionsById($personUuid);
                        $connectionsData = $connections['data'] ?? [];
                    }

                    $activeOnlyConnections = (bool) ($this->config['relationships']['display']['member_card_active_only'] ?? false);
                    if (is_array($connectionsData) && !empty($connectionsData)) {
                        foreach ($connectionsData as $conn) {
                            $orgId = $conn['relationships']['organization']['data']['id'] ?? null;
                            if (trim((string) $orgId) !== trim((string) $orgUuid)) {
                                continue;
                            }

                            if ($activeOnlyConnections) {
                                $isConnectionActive = (bool) ($conn['attributes']['active'] ?? false);
                                if (!$isConnectionActive) {
                                    continue;
                                }
                            }

                            $rawRelationshipType = (string) ($conn['attributes']['type'] ?? '');
                            $slug = $this->normalizeRelationshipType($rawRelationshipType);
                            $connId = $conn['attributes']['uuid'] ?? null;

                            if ($slug !== '') {
                                $relationshipSlugs[] = $slug;
                                if (!isset($relationshipNamesBySlug[$slug])) {
                                    $relationshipNamesBySlug[$slug] = $this->resolveRelationshipLabel($slug, $relationshipTypeLabels);
                                }
                            }

                            if ($relationshipDescription === null) {
                                $connDescription = $conn['attributes']['description'] ?? null;
                                if (is_string($connDescription) && $connDescription !== '') {
                                    $relationshipDescription = $connDescription;
                                }
                            }

                            // Collect all connection IDs for this organization (like legacy system)
                            if ($connId) {
                                $personConnectionIds[] = $connId;
                            }
                        }

                        $relationshipSlugs = array_values(array_unique($relationshipSlugs));
                        if (in_array('primary_contact', $relationshipSlugs, true)) {
                            $relationshipSlugs = array_values(array_diff($relationshipSlugs, ['primary_contact']));
                            array_unshift($relationshipSlugs, 'primary_contact');
                        }
                    }
                } catch (\Throwable $e) {
                    $logger->warning(
                        '[OrgMan] Failed to process person connections',
                        [
                            'source'    => 'wicket-orgman',
                            'person_id' => $personUuid,
                            'error'     => $e->getMessage(),
                        ]
                    );
                }
            }

            if (!empty($allowedTypes)) {
                $relationshipSlugs = array_values(array_filter($relationshipSlugs, static function ($slug) use ($allowedTypes): bool {
                    return in_array($slug, $allowedTypes, true);
                }));
            }

            if (!empty($excludedTypes)) {
                $relationshipSlugs = array_values(array_filter($relationshipSlugs, static function ($slug) use ($excludedTypes): bool {
                    return !in_array($slug, $excludedTypes, true);
                }));
            }

            if (!$isLazy && (!empty($allowedTypes) || !empty($excludedTypes)) && empty($relationshipSlugs)) {
                $logger->debug('[OrgMan] Skipping member due to relationship filter', [
                    'source' => 'wicket-orgman',
                    'person_uuid' => $personUuid,
                    'isLazy' => $isLazy,
                    'allowedTypes' => $allowedTypes,
                    'excludedTypes' => $excludedTypes,
                    'relationshipSlugs' => $relationshipSlugs,
                ]);
                continue;
            }

            $relationshipNames = [];
            foreach ($relationshipSlugs as $slug) {
                $label = $relationshipNamesBySlug[$slug] ?? $this->resolveRelationshipLabel($slug, $relationshipTypeLabels);
                if ($label !== '') {
                    $relationshipNames[] = $label;
                }
            }

            $confirmedAt = null;
            if ($personUuid && isset($userIndex[$personUuid])) {
                $userData = $userIndex[$personUuid];
                $confirmedAt = $userData['attributes']['confirmed_at']
                    ?? ($userData['data']['attributes']['confirmed_at'] ?? null);
                \Wicket()->log()->warning('[OrgMan] Found confirmed_at from userIndex', [
                    'person_uuid' => $personUuid,
                    'confirmed_at' => $confirmedAt,
                    'user_data_keys' => array_keys($userData),
                ]);
            }

            if (empty($confirmedAt)) {
                $confirmedAt = $personAttributes['confirmed_at'] ?? $memberAttributes['confirmed_at'] ?? null;
                \Wicket()->log()->warning('[OrgMan] Fallback confirmed_at', [
                    'person_uuid' => $personUuid,
                    'confirmed_at' => $confirmedAt,
                ]);
            }

            $memberRow = [
                'person_uuid'           => $personUuid,
                'first_name'            => $firstName,
                'last_name'             => $lastName,
                'full_name'             => trim($firstName . ' ' . $lastName),
                'title'                 => $title,
                'email'                 => $email,
                'roles'                 => $roles,
                'current_roles'         => !empty($currentRolesList) ? $currentRolesList : $roles,
                'confirmed_at'          => $confirmedAt,
                'status'                => $personAttributes['status'] ?? null,
                'job_level'             => $personAttributes['job_level'] ?? null,
                'relationship_names_list' => array_values(array_unique($relationshipNames)),
                'relationship_slugs'      => array_values(array_unique($relationshipSlugs)),
                'relationship_description' => $relationshipDescription,
                'is_owner'              => (!empty($ownerId) && $personUuid && $personUuid === $ownerId),
                'person_connection_ids_list' => array_values(array_unique(array_filter(array_map('strval', $personConnectionIds)))),
                'person_membership_id'  => $member['id'] ?? null,
                'lazy_loaded'           => !$isLazy,
            ];

            $personKey = is_string($personUuid) ? trim($personUuid) : '';
            if ($personKey !== '') {
                // Add all person_membership records (no deduplication by person)
                $members[] = $this->finalizePreparedMemberRow($memberRow);
                $logger->debug('[OrgMan] Added member record (allowing duplicates)', [
                    'source' => 'wicket-orgman',
                    'person_uuid' => $personUuid,
                    'person_membership_id' => $member['id'] ?? null,
                    'members_count' => count($members),
                ]);
                $loopSuccess++;
            } else {
                $membersWithoutPerson[] = $memberRow;
                $logger->debug('[OrgMan] Added member to membersWithoutPerson', [
                    'source' => 'wicket-orgman',
                    'person_uuid' => $personUuid,
                    'members_without_person_count' => count($membersWithoutPerson),
                ]);
                $loopSuccess++;
            }
        }

        $logger->debug('[OrgMan] Loop processing complete', [
            'source' => 'wicket-orgman',
            'raw_members_count' => count($rawMembers),
            'loop_iterations' => $loopCounter,
            'loop_continues' => $loopContinue,
            'loop_success' => $loopSuccess,
            'final_members_count' => count($members),
            'final_members_without_person' => count($membersWithoutPerson),
        ]);

        // Add members without person data (if any)
        foreach ($membersWithoutPerson as $memberRow) {
            $members[] = $this->finalizePreparedMemberRow($memberRow);
        }

        $logger->debug('[OrgMan] prepareMembersResult output', [
            'source' => 'wicket-orgman',
            'final_members_count' => count($members),
            'members_without_person_count' => count($membersWithoutPerson),
            'total_items_from_meta' => $totalItems ?? 'not_set',
        ]);

        $totalItems = 0;
        if (is_array($membersResponse)) {
            if (isset($membersResponse['meta']['page']['total_items'])) {
                $totalItems = (int) $membersResponse['meta']['page']['total_items'];
            } elseif (isset($membersResponse['meta']['total'])) {
                $totalItems = (int) $membersResponse['meta']['total'];
            }
        }

        if (0 === $totalItems) {
            $totalItems = count($members);
        }

        $totalPages = (int) max(1, ceil($totalItems / max(1, $size)));

        return [
            'members'    => $members,
            'pagination' => [
                'currentPage' => $page,
                'totalPages'  => $totalPages,
                'pageSize'    => $size,
                'totalItems'  => $totalItems,
            ],
            'org_uuid'   => $orgUuid,
            'query'      => $query,
        ];
    }

    /**
     * Normalize the membership members response into an array structure.
     *
     * @param mixed $response Raw response from helper or API client.
     * @return array|null
     */
    private function normalizeMembershipResponse($response): ?array
    {
        if (is_array($response)) {
            return $response;
        }

        $body = null;

        if ($response instanceof \Psr\Http\Message\ResponseInterface) {
            $body = (string) $response->getBody();
        } elseif (is_object($response) && method_exists($response, 'body')) {
            $body = (string) $response->body();
        } elseif (is_object($response) && method_exists($response, 'getBody')) {
            $body = (string) $response->getBody();
        } elseif (is_string($response)) {
            $body = $response;
        }

        if (null === $body) {
            \Wicket()->log()->debug(
                '[OrgMan] Membership response had no body to decode',
                [
                    'source'   => 'wicket-orgman',
                    'respType' => is_object($response) ? get_class($response) : gettype($response),
                ]
            );

            return null;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Wicket()->log()->warning(
                '[OrgMan] Unable to decode membership response JSON',
                [
                    'source' => 'wicket-orgman',
                    'error'  => json_last_error_msg(),
                ]
            );

            return null;
        }

        return $decoded;
    }

    /**
     * Get current roles for a person in a specific organization using MDP API.
     *
     * @param string $personUuid The person UUID
     * @param string $orgUuid The organization UUID
     * @return array Array of role names
     */
    public function getPersonCurrentRolesByOrgId($personUuid, $orgUuid)
    {
        if (!empty($personUuid) && !empty($orgUuid)) {
            try {
                $permissionRoles = $this->permissionService()->getPersonCurrentRolesByOrgId((string) $personUuid, (string) $orgUuid);
                if (is_array($permissionRoles) && !empty($permissionRoles)) {
                    return $this->normalizeRoleList($permissionRoles);
                }
            } catch (\Throwable $e) {
                // Fall through to legacy endpoint request.
            }
        }

        if (!function_exists('wicket_api_client')) {
            return [];
        }

        $client = wicket_api_client();

        $response = $client->get('/people/' . $personUuid . '/roles', [
            'query' => [
                'page[number]' => 1,
                'page[size]' => 100,
                'fields[organizations][]' => 'legal_name_en',
                'fields[organizations][]' => 'legal_name_fr',
                'fields[organizations][]' => 'type',
                'include' => 'resource',
                'sort' => '-global,name',
            ],
        ]);

        if (isset($response['data'])) {
            $data = $response['data'];
            $roles = [];

            // Filter the roles based on the organization UUID
            foreach ($data as $role) {
                // Include org-specific roles
                if (
                    isset($role['relationships']['resource']['data']['id'])
                    && $role['relationships']['resource']['data']['id'] === $orgUuid
                ) {
                    $roles[] = $role['attributes']['name'];
                }
            }

            return $this->normalizeRoleList($roles);
        }

        return [];
    }

    /**
     * Get formatted roles string for display.
     *
     * @param string $personUuid The person UUID
     * @param string $orgUuid The organization UUID
     * @return string Formatted roles string
     */
    public function getFormattedRolesString($personUuid, $orgUuid)
    {
        $roles = $this->getPersonCurrentRolesByOrgId($personUuid, $orgUuid);

        if (empty($roles)) {
            return '';
        }

        return implode(', ', $roles);
    }

    /**
     * Check if a user is confirmed by their UUID.
     *
     * @param string|null $personUuid The person UUID. If null, checks current user.
     * @return bool True if the user is confirmed, false if not or if user not found
     */
    public function isUserConfirmed(?string $personUuid = null): bool
    {
        // If no UUID provided, get current user's UUID
        if (empty($personUuid)) {
            $personUuid = function_exists('wicket_current_person_uuid') ? wicket_current_person_uuid() : null;
        }

        if (empty($personUuid)) {
            return false;
        }

        try {
            $person = $this->getPersonById($personUuid);

            if (!is_array($person) || !isset($person['data']['attributes'])) {
                return false;
            }

            // Check confirmation status using the same logic as member display
            $attributes = (array) ($person['data']['attributes'] ?? []);
            $confirmedAt = $attributes['user']['confirmed_at']
                ?? ($person['data']['user']['confirmed_at']
                    ?? ($attributes['confirmed_at'] ?? null));

            // User is confirmed if confirmed_at is not null and not empty
            return !empty($confirmedAt);

        } catch (\Throwable $e) {
            // Log error for debugging but return false for safety
            $logger = \Wicket()->log();
            $logger->warning(
                '[OrgMan] Failed to check user confirmation status',
                [
                    'source'    => 'wicket-orgman',
                    'person_id' => $personUuid,
                    'error'     => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * Get confirmation status of current user.
     *
     * @return bool True if current user is confirmed, false otherwise
     */
    public function isCurrentUserConfirmed(): bool
    {
        return $this->isUserConfirmed();
    }

    /**
     * Check if user is confirmed by UUID (alias for isUserConfirmed with current user fallback).
     *
     * @param string|null $personUuid The person UUID. If null or empty, checks current user.
     * @return bool True if the user is confirmed, false if not or if user not found
     */
    public function checkUserConfirmation(?string $personUuid = null): bool
    {
        return $this->isUserConfirmed($personUuid);
    }

    /**
     * Get person data by UUID using the Wicket API.
     *
     * @param string $personUuid The person UUID
     * @return array|null Person data or null on failure
     */
    public function getPersonById($personUuid)
    {
        if (!function_exists('wicket_api_client')) {
            return null;
        }

        $client = wicket_api_client();

        try {
            $response = $client->get('/people/' . $personUuid);

            return $response;
        } catch (\Exception $e) {
            // Log error if needed
            return null;
        }
    }

    /**
     * Update member roles for a person in an organization.
     *
     * @param string $personUuid The person UUID
     * @param string $orgUuid The organization UUID
     * @param string $membershipUuid The organization membership UUID
     * @param array $roles Array of role slugs to assign
     * @return array|WP_ERROR Updated member data or WP_Error on failure
     */
    public function updateMemberRoles($personUuid, $orgUuid, $membershipUuid, $roles)
    {
        if (!function_exists('wicket_api_client')) {
            return new \WP_Error('api_unavailable', 'Wicket API client is not available.');
        }

        if (empty($personUuid) || empty($orgUuid) || empty($membershipUuid)) {
            return new \WP_Error('invalid_params', 'Person UUID, organization UUID, and membership UUID are required.');
        }

        try {
            $client = wicket_api_client();
            $logger = \Wicket()->log();
            $log_context = ['source' => 'wicket-orgman', 'action' => 'update_member_roles'];

            // Get current person memberships (paginate to avoid selecting stale/inactive records on partial pages)
            $memberships_endpoint = '/organization_memberships/' . rawurlencode($membershipUuid) . '/person_memberships';
            $page = 1;
            $totalPages = 1;
            $person_memberships = [];

            do {
                $response = $client->get($memberships_endpoint . '?' . http_build_query([
                    'page[number]' => $page,
                    'page[size]' => 100,
                    'include' => 'person',
                ]));

                $rows = is_array($response['data'] ?? null) ? $response['data'] : [];
                foreach ($rows as $membership) {
                    $currentPersonId = $membership['relationships']['person']['data']['id'] ?? null;
                    if ($currentPersonId === $personUuid) {
                        $person_memberships[] = $membership;
                    }
                }

                $pageMeta = $response['meta']['page'] ?? [];
                $totalPages = max(1, (int) ($pageMeta['total_pages'] ?? 1));
                $page++;
            } while ($page <= $totalPages);

            if (empty($person_memberships)) {
                return new \WP_Error('membership_not_found', 'Person membership not found in this organization.');
            }

            if ($logger) {
                $logger->debug('[OrgMan] update_member_roles candidate person_memberships', $log_context + [
                    'org_uuid' => $orgUuid,
                    'membership_uuid' => $membershipUuid,
                    'person_uuid' => $personUuid,
                    'candidate_count' => count($person_memberships),
                    'candidates' => array_map(static function (array $membership): array {
                        return [
                            'id' => (string) ($membership['id'] ?? ''),
                            'active' => $membership['attributes']['active'] ?? null,
                            'in_grace' => $membership['attributes']['in_grace'] ?? null,
                            'starts_at' => $membership['attributes']['starts_at'] ?? null,
                            'ends_at' => $membership['attributes']['ends_at'] ?? null,
                        ];
                    }, $person_memberships),
                ]);
            }

            // Prefer active/in_grace record when duplicates exist.
            usort($person_memberships, static function (array $a, array $b): int {
                $aActive = !empty($a['attributes']['active']) || !empty($a['attributes']['in_grace']);
                $bActive = !empty($b['attributes']['active']) || !empty($b['attributes']['in_grace']);
                if ($aActive !== $bActive) {
                    return $aActive ? -1 : 1;
                }

                $aEndsAt = strtotime((string) ($a['attributes']['ends_at'] ?? '')) ?: PHP_INT_MAX;
                $bEndsAt = strtotime((string) ($b['attributes']['ends_at'] ?? '')) ?: PHP_INT_MAX;
                if ($aEndsAt === $bEndsAt) {
                    return 0;
                }

                return ($aEndsAt > $bEndsAt) ? -1 : 1;
            });

            $person_membership = $person_memberships[0];

            if (!$person_membership) {
                return new \WP_Error('membership_not_found', 'Person membership not found in this organization.');
            }

            $require_active_membership = (bool) ($this->config['member_management']['edit']['require_active_membership_for_role_updates'] ?? false);
            if ($require_active_membership) {
                $has_active_row = false;
                foreach ($person_memberships as $membership) {
                    if (!empty($membership['attributes']['active']) || !empty($membership['attributes']['in_grace'])) {
                        $has_active_row = true;
                        break;
                    }
                }

                $is_active_membership = $has_active_row;
                if (!$is_active_membership) {
                    try {
                        // Fallback: ask API for "active now" rows to avoid stale/incomplete attributes on list endpoints.
                        $query_response = $client->post('/person_memberships/query', [
                            'json' => [
                                'filter' => [
                                    'organization_membership_uuid_in' => [$membershipUuid],
                                    'person_uuid_in' => [$personUuid],
                                    'active_at' => 'now',
                                ],
                                'page' => [
                                    'number' => 1,
                                    'size' => 1,
                                ],
                            ],
                        ]);

                        $query_rows = is_array($query_response['data'] ?? null) ? $query_response['data'] : [];
                        $is_active_membership = !empty($query_rows);
                    } catch (\Throwable $active_lookup_exception) {
                        if ($logger) {
                            $logger->warning('[OrgMan] update_member_roles active_at fallback query failed', $log_context + [
                                'org_uuid' => $orgUuid,
                                'membership_uuid' => $membershipUuid,
                                'person_uuid' => $personUuid,
                                'error' => $active_lookup_exception->getMessage(),
                            ]);
                        }
                    }
                }

                if ($logger) {
                    $logger->debug('[OrgMan] update_member_roles active-membership guard result', $log_context + [
                        'org_uuid' => $orgUuid,
                        'membership_uuid' => $membershipUuid,
                        'person_uuid' => $personUuid,
                        'has_active_row' => $has_active_row,
                        'is_active_membership' => $is_active_membership,
                    ]);
                }

                if (!$is_active_membership) {
                    return new \WP_Error(
                        'inactive_member_role_update_forbidden',
                        'Cannot update roles for an inactive member.'
                    );
                }
            }

            // Prepare the update payload with roles
            $attributes = $person_membership['attributes'] ?? [];
            $attributes['roles'] = $roles;

            $update_payload = [
                'data' => [
                    'type' => $person_membership['type'],
                    'id' => $person_membership['id'],
                    'attributes' => $attributes,
                ],
            ];

            // Update roles using the correct API approach
            // Based on legacy wicket_assign_role and wicket_remove_role functions

            // Define which roles we can manage (organization-specific roles only).
            // Respect edit-permissions modal allow/deny config so hidden roles are not removed.
            $manageable_roles = ['membership_manager', 'org_editor'];
            $permissions_modal_config = is_array($this->config['member_management']['permissions_modal'] ?? null)
                ? $this->config['member_management']['permissions_modal']
                : [];
            $modal_allowlist = is_array($permissions_modal_config['allowlist'] ?? null)
                ? $permissions_modal_config['allowlist']
                : [];
            if ($modal_allowlist === [] && is_array($permissions_modal_config['allowed_roles'] ?? null)) {
                $modal_allowlist = $permissions_modal_config['allowed_roles'];
            }
            $modal_denylist = is_array($permissions_modal_config['denylist'] ?? null)
                ? $permissions_modal_config['denylist']
                : [];
            if ($modal_denylist === [] && is_array($permissions_modal_config['excluded_roles'] ?? null)) {
                $modal_denylist = $permissions_modal_config['excluded_roles'];
            }

            $normalizeRoleList = function (array $role_list): array {
                $normalized_roles = [];
                foreach ($role_list as $role_name) {
                    $role_slug = $this->normalizeRoleSlug((string) $role_name);
                    if ($role_slug === '') {
                        continue;
                    }
                    $normalized_roles[] = $role_slug;
                }

                return array_values(array_unique($normalized_roles));
            };

            $normalized_modal_allowlist = $normalizeRoleList($modal_allowlist);
            $normalized_modal_denylist = $normalizeRoleList($modal_denylist);
            $manageable_roles = $normalizeRoleList($manageable_roles);

            if (!empty($normalized_modal_allowlist)) {
                $manageable_roles = array_values(array_intersect($manageable_roles, $normalized_modal_allowlist));
            }

            if (!empty($normalized_modal_denylist)) {
                $manageable_roles = array_values(array_diff($manageable_roles, $normalized_modal_denylist));
            }

            // Only consider manageable roles for add/remove operations. Compare against org-scoped
            // role assignments to avoid false positives from roles held in other organizations.
            $desired_manageable_roles = array_values(array_intersect($roles, $manageable_roles));
            $current_manageable_roles = array_values(array_intersect(
                $this->getPersonCurrentRolesByOrgId($personUuid, $orgUuid),
                $manageable_roles
            ));

            // Determine which manageable roles to add and which to remove
            $roles_to_add = array_diff($desired_manageable_roles, $current_manageable_roles);
            $roles_to_remove = array_diff($current_manageable_roles, $desired_manageable_roles);
            if ($logger) {
                $logger->debug('[OrgMan] update_member_roles role diff', $log_context + [
                    'org_uuid' => $orgUuid,
                    'membership_uuid' => $membershipUuid,
                    'person_uuid' => $personUuid,
                    'requested_roles' => array_values($roles),
                    'manageable_roles' => $manageable_roles,
                    'current_manageable_roles' => array_values($current_manageable_roles),
                    'desired_manageable_roles' => array_values($desired_manageable_roles),
                    'roles_to_add' => array_values($roles_to_add),
                    'roles_to_remove' => array_values($roles_to_remove),
                ]);
            }

            // Remove roles that are no longer needed
            foreach ($roles_to_remove as $role_name) {
                if ($logger) {
                    $logger->debug('[OrgMan] update_member_roles removing role', $log_context + [
                        'org_uuid' => $orgUuid,
                        'membership_uuid' => $membershipUuid,
                        'person_uuid' => $personUuid,
                        'role' => $role_name,
                    ]);
                }

                $remove_role_result = function_exists('wicket_remove_role')
                    ? wicket_remove_role($personUuid, $role_name, $orgUuid)
                    : false;

                if (!$remove_role_result) {
                    if ($logger) {
                        $logger->error('[OrgMan] update_member_roles failed removing role', $log_context + [
                            'org_uuid' => $orgUuid,
                            'membership_uuid' => $membershipUuid,
                            'person_uuid' => $personUuid,
                            'role' => $role_name,
                        ]);
                    }

                    return new \WP_Error(
                        'role_remove_failed',
                        sprintf("Failed to remove role '%s'.", $role_name)
                    );
                }

                $org_roles_after_remove = $this->getPersonCurrentRolesByOrgId($personUuid, $orgUuid);
                $role_still_present = in_array($role_name, $org_roles_after_remove, true);
                if ($role_still_present) {
                    if ($logger) {
                        $logger->error('[OrgMan] update_member_roles remove reported success but role remains', $log_context + [
                            'org_uuid' => $orgUuid,
                            'membership_uuid' => $membershipUuid,
                            'person_uuid' => $personUuid,
                            'role' => $role_name,
                            'org_roles_after_remove' => array_values($org_roles_after_remove),
                        ]);
                    }

                    return new \WP_Error(
                        'role_remove_verify_failed',
                        sprintf("Failed to verify removal of role '%s'.", $role_name)
                    );
                }
            }

            // Add new roles
            foreach ($roles_to_add as $role_name) {
                if ($logger) {
                    $logger->debug('[OrgMan] update_member_roles adding role', $log_context + [
                        'org_uuid' => $orgUuid,
                        'membership_uuid' => $membershipUuid,
                        'person_uuid' => $personUuid,
                        'role' => $role_name,
                    ]);
                }

                if (!function_exists('wicket_assign_role') || !wicket_assign_role($personUuid, $role_name, $orgUuid)) {
                    if ($logger) {
                        $logger->error('[OrgMan] update_member_roles failed adding role', $log_context + [
                            'org_uuid' => $orgUuid,
                            'membership_uuid' => $membershipUuid,
                            'person_uuid' => $personUuid,
                            'role' => $role_name,
                        ]);
                    }

                    return new \WP_Error(
                        'role_add_failed',
                        sprintf("Failed to add role '%s'.", $role_name)
                    );
                }
            }

            // Get person data for response
            $person_data = $this->getPersonById($personUuid);

            return [
                'success' => true,
                'first_name' => $person_data['data']['attributes']['first_name'] ?? '',
                'last_name' => $person_data['data']['attributes']['last_name'] ?? '',
                'roles' => $roles,
            ];

        } catch (\Exception $e) {
            if (isset($logger) && $logger) {
                $logger->error('[OrgMan] update_member_roles exception', $log_context + [
                    'org_uuid' => $orgUuid,
                    'membership_uuid' => $membershipUuid,
                    'person_uuid' => $personUuid,
                    'requested_roles' => is_array($roles) ? array_values($roles) : [],
                    'error' => $e->getMessage(),
                ]);
            }

            return new \WP_Error('update_exception', 'Failed to update member roles: ' . $e->getMessage());
        }
    }

    /**
     * Update member relationship type and optionally sync roles.
     *
     * @param string $personUuid The person UUID
     * @param string $orgUuid The organization UUID
     * @param string $relationshipType The new relationship type
     * @return array|WP_Error Updated member data or WP_Error on failure
     */
    public function updateMemberRelationship($personUuid, $orgUuid, $relationshipType)
    {
        if (!function_exists('wicket_api_client')) {
            return new \WP_Error('api_unavailable', 'Wicket API client is not available.');
        }

        if (empty($personUuid) || empty($orgUuid) || empty($relationshipType)) {
            return new \WP_Error('invalid_params', 'Person UUID, organization UUID, and relationship type are required.');
        }

        try {
            // Update the connection type
            $connection_result = $this->connectionService()->updateConnectionType($personUuid, $orgUuid, $relationshipType);

            if (is_wp_error($connection_result)) {
                return $connection_result;
            }

            // Check if we should automatically update roles based on relationship type
            $config = \OrgManagement\Config\OrgManConfig::get();
            $relationship_based_permissions = $config['access']['permissions']['relationship_grants']['enabled'] ?? false;

            if ($relationship_based_permissions) {
                // Get the role mapping for this relationship type
                $relationship_roles_map = $config['access']['permissions']['relationship_grants']['roles_by_type'] ?? [];
                $new_roles = $relationship_roles_map[$relationshipType] ?? [];

                // Get all possible relationship-type-based roles
                $all_relationship_roles = [];
                foreach ($relationship_roles_map as $roles) {
                    $all_relationship_roles = array_merge($all_relationship_roles, $roles);
                }
                $all_relationship_roles = array_unique($all_relationship_roles);

                // Get current roles
                $current_roles = $this->getPersonCurrentRolesByOrgId($personUuid, $orgUuid);

                // Determine which relationship-based roles to remove
                $roles_to_remove = array_intersect($current_roles, $all_relationship_roles);

                // Determine which relationship-based roles to add
                $roles_to_add = array_diff($new_roles, $current_roles);

                // Remove old relationship-based roles
                if (!empty($roles_to_remove)) {
                    foreach ($roles_to_remove as $role) {
                        if (function_exists('wicket_remove_role')) {
                            wicket_remove_role($personUuid, $role, $orgUuid);
                        }
                    }
                }

                // Add new relationship-based roles
                if (!empty($roles_to_add)) {
                    foreach ($roles_to_add as $role) {
                        if (function_exists('wicket_assign_role')) {
                            wicket_assign_role($personUuid, $role, $orgUuid);
                        }
                    }
                }
            }

            // Get person data for response
            $person_data = $this->getPersonById($personUuid);

            return [
                'success' => true,
                'first_name' => $person_data['data']['attributes']['first_name'] ?? '',
                'last_name' => $person_data['data']['attributes']['last_name'] ?? '',
                'relationship_type' => $relationshipType,
            ];

        } catch (\Exception $e) {
            return new \WP_Error('update_exception', 'Failed to update member relationship: ' . $e->getMessage());
        }
    }

    /**
     * Update member relationship description.
     *
     * @param string $personUuid The person UUID
     * @param string $orgUuid The organization UUID
     * @param string $description The relationship description
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function updateMemberDescription($personUuid, $orgUuid, $description)
    {
        if (!function_exists('wicket_api_client')) {
            return new \WP_Error('api_unavailable', 'Wicket API client is not available.');
        }

        if (empty($personUuid) || empty($orgUuid)) {
            return new \WP_Error('invalid_params', 'Person UUID and organization UUID are required.');
        }

        try {
            $description = is_string($description) ? sanitize_textarea_field($description) : '';

            return $this->connectionService()->updateConnectionDescription($personUuid, $orgUuid, $description);
        } catch (\Exception $e) {
            return new \WP_Error('update_exception', 'Failed to update member description: ' . $e->getMessage());
        }
    }
}
