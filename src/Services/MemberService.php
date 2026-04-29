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
     * @var MembershipRosterReader
     */
    private MembershipRosterReader $reader;

    /**
     * Constructor.
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
        $this->config = \OrgManagement\Config\OrgManConfig::get();
        $this->reader = new MembershipRosterReader($configService);
        $this->initStrategies();
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
        return $this->reader->getMembershipMembers($membershipUuid, $args);
    }

    public function clearMembersCache(string $membershipUuid): void
    {
        $this->reader->clearMembersCache($membershipUuid);
    }

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

        $result = $this->reader->prepareMembersResult(
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

        // Pre-warm lazy-details cache for each member when full data is available.
        if (!$lazy && !empty($result['members'])) {
            $cacheService = new CacheService();
            $gen = $cacheService->getMembershipGeneration($membershipUuid);
            foreach ($result['members'] as $member) {
                $personUuid = $member['person_uuid'] ?? '';
                if ($personUuid !== '') {
                    $lazyCacheKey = 'orgman_lazy_details_' . md5($personUuid . $orgUuid . $membershipUuid . $gen);
                    $cacheService->set($lazyCacheKey, $member);
                }
            }
        }

        return $result;
    }

    public function getMemberByPersonUuid(string $personUuid, string $membershipUuid, string $orgUuid): ?array
    {
        return $this->reader->getMemberByPersonUuid($personUuid, $membershipUuid, $orgUuid);
    }

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

    public function getPersonCurrentRolesByOrgId($personUuid, $orgUuid)
    {
        return $this->reader->getPersonCurrentRolesByOrgId($personUuid, $orgUuid);
    }

    public function getFormattedRolesString($personUuid, $orgUuid)
    {
        return $this->reader->getFormattedRolesString($personUuid, $orgUuid);
    }

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
        return $this->reader->getPersonById($personUuid);
    }

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
                $logger->debug('update_member_roles candidate person_memberships', $log_context + [
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
                            $logger->warning('update_member_roles active_at fallback query failed', $log_context + [
                                'org_uuid' => $orgUuid,
                                'membership_uuid' => $membershipUuid,
                                'person_uuid' => $personUuid,
                                'error' => $active_lookup_exception->getMessage(),
                            ]);
                        }
                    }
                }

                if ($logger) {
                    $logger->debug('update_member_roles active-membership guard result', $log_context + [
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
                    $role_slug = $this->reader->normalizeRoleSlug((string) $role_name);
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
                $logger->debug('update_member_roles role diff', $log_context + [
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
                    $logger->debug('update_member_roles removing role', $log_context + [
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
                        $logger->error('update_member_roles failed removing role', $log_context + [
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
                        $logger->error('update_member_roles remove reported success but role remains', $log_context + [
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
                    $logger->debug('update_member_roles adding role', $log_context + [
                        'org_uuid' => $orgUuid,
                        'membership_uuid' => $membershipUuid,
                        'person_uuid' => $personUuid,
                        'role' => $role_name,
                    ]);
                }

                if (!function_exists('wicket_assign_role') || !wicket_assign_role($personUuid, $role_name, $orgUuid)) {
                    if ($logger) {
                        $logger->error('update_member_roles failed adding role', $log_context + [
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
                $logger->error('update_member_roles exception', $log_context + [
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
