<?php
/**
 * Member Model for handling member data.
 *
 * @package OrgManagement
 */

namespace OrgManagement\Services;

use OrgManagement\Services\PermissionService;
use OrgManagement\Services\ConnectionService;
use OrgManagement\Services\MembershipService;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles data operations for organization members.
 */
class MemberService {

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
    private $config_service;

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
     * @param ConfigService $config_service
     */
    public function __construct( ConfigService $config_service ) {
        $this->config_service = $config_service;
        $this->config = \OrgManagement\Config\get_config();
        $this->init_strategies();
    }

    /**
     * Helper method to get cached data if cache is enabled.
     *
     * @param string $cache_key The cache key.
     * @return mixed|false Cached data or false if not found/disabled.
     */
    private function get_cached_data( $cache_key ) {
        if ( ! \OrgManagement\Helpers\ConfigHelper::is_cache_enabled() ) {
            return false;
        }
        return get_transient( $cache_key );
    }

    /**
     * Helper method to set cached data if cache is enabled.
     *
     * @param string $cache_key The cache key.
     * @param mixed $data The data to cache.
     * @return void
     */
    private function set_cached_data( $cache_key, $data ) {
        if ( \OrgManagement\Helpers\ConfigHelper::is_cache_enabled() ) {
            $cache_duration = \OrgManagement\Helpers\ConfigHelper::get_cache_duration();
            set_transient( $cache_key, $data, $cache_duration );
        }
    }

    /**
     * Initialize the available strategies.
     */
    private function init_strategies() {
        $this->strategies['cascade'] = new \OrgManagement\Services\Strategies\CascadeStrategy();
        $this->strategies['direct'] = new \OrgManagement\Services\Strategies\DirectAssignmentStrategy();
        $this->strategies['groups'] = new \OrgManagement\Services\Strategies\GroupsStrategy();
        $this->strategies['membership_cycle'] = new \OrgManagement\Services\Strategies\MembershipCycleStrategy();
    }

    /**
     * Get the current roster management strategy.
     *
     * @return RosterManagementStrategy
     */
    private function get_strategy() {
        $mode = $this->config_service->get_roster_mode();
        return $this->strategies[ $mode ] ?? $this->strategies['cascade'];
    }

    /**
     * Add a member to an organization.
     *
     * @param string $org_id The organization ID.
     * @param array  $member_data Data for the new member.
     * @return array|\WP_Error
     */
    public function add_member( $org_id, $member_data, $context = [] ) {
        return $this->get_strategy()->add_member( $org_id, $member_data, $context );
    }

    /**
     * Remove a member from an organization.
     *
     * @param string $org_id The organization ID.
     * @param string $person_uuid The UUID of the person to remove.
     * @param array  $context Additional context for the operation.
     * @return array|\WP_Error
     */
    public function remove_member( $org_id, $person_uuid, $context = [] ) {
        return $this->get_strategy()->remove_member( $org_id, $person_uuid, $context );
    }

    /**
     * Check if a user has the required roles within an organization.
     *
     * @param string $person_uuid The user's UUID.
     * @param string $org_id The organization ID.
     * @param array  $roles The roles to check for.
     * @return bool True if the user has at least one of the roles, false otherwise.
     */
    public function has_role( $person_uuid, $org_id, $roles ) {
        return $this->person_has_org_roles( $person_uuid, $roles, $org_id, false );
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
    public function person_has_org_roles( $person_uuid, $roles, $org_id, $all_true = false ) {
        if ( empty( $person_uuid ) || empty( $roles ) || empty( $org_id ) ) {
            return false;
        }

        // Get current person roles for the organization
        $current_roles = $this->permission_service()->get_person_current_roles_by_org_id( $person_uuid, $org_id );

        if ( ! is_array( $current_roles ) || empty( $current_roles ) ) {
            return false;
        }

        // Normalize roles to array
        if ( ! is_array( $roles ) ) {
            if ( str_contains( $roles, ',' ) ) {
                $roles = explode( ',', $roles );
            } else {
                $roles = [ $roles ];
            }
        }

        // Sanitize roles
        $roles = array_map( 'sanitize_key', array_filter( $roles ) );

        if ( empty( $roles ) ) {
            return false;
        }

        if ( $all_true ) {
            // All roles must be present
            foreach ( $roles as $role ) {
                if ( ! in_array( $role, $current_roles, true ) ) {
                    return false;
                }
            }
            return true;
        } else {
            // At least one role must be present
            foreach ( $roles as $role ) {
                if ( in_array( $role, $current_roles, true ) ) {
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
    private function permission_service(): PermissionService {
        if ( ! isset( $this->permissionService ) ) {
            $this->permissionService = new PermissionService();
        }

        return $this->permissionService;
    }

    /**
     * Lazily instantiate ConnectionService.
     *
     * @return ConnectionService
     */
    private function connectionService(): ConnectionService {
        if ( ! isset( $this->connectionService ) ) {
            $this->connectionService = new ConnectionService();
        }

        return $this->connectionService;
    }

    /**
     * Lazily instantiate MembershipService.
     *
     * @return MembershipService
     */
    private function membership_service(): MembershipService {
        if ( ! isset( $this->membershipService ) ) {
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
    public function getMembershipMembers( string $membershipUuid, array $args = [] ): ?array {
        if ( empty( $membershipUuid ) ) {
            return null;
        }

        $defaultPageSize = 10;

        $page = max( 1, (int) ( $args['page'] ?? 1 ) );
        $size = max( 1, (int) ( $args['size'] ?? $defaultPageSize ) );
        $searchTerm = isset( $args['query'] ) ? sanitize_text_field( (string) $args['query'] ) : '';

        $logger = wc_get_logger();

        // Cache initial load only (no search term)
        if (empty($searchTerm)) {
            $cache_key = 'orgman_members_initial_' . md5($membershipUuid . '_' . $page . '_' . $size);
            $cached_data = $this->get_cached_data($cache_key);

            if (false !== $cached_data) {
                return $cached_data;
            }
        }

        if ( '' !== $searchTerm ) {
            try {
                $searchResult = $this->membership_service()->membership_search_members(
                    $membershipUuid,
                    [
                        'page'  => $page,
                        'size'  => $size,
                        'query' => $searchTerm,
                    ]
                );

                if ( ! is_wp_error( $searchResult ) && ! empty( $searchResult ) ) {
                    return $searchResult;
                }

                if ( is_wp_error( $searchResult ) ) {
                    $logger->warning(
                        '[OrgMan] MembershipService member search returned WP_Error',
                        [
                            'source'          => 'wicket-orgman',
                            'membership_uuid' => $membershipUuid,
                            'error_code'      => $searchResult->get_error_code(),
                            'error_message'   => $searchResult->get_error_message(),
                        ]
                    );
                } else {

                }
            } catch ( \Throwable $searchException ) {
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

        if ( '' !== $searchTerm && function_exists( 'wicket_api_client' ) ) {
            $queryArgs = [
                'page[number]' => $page,
                'page[size]'   => $size,
                'include'      => 'person,emails,phones',
            ];

            $payload = [
                'filter' => [
                    'organization_membership_uuid_in'                    => [ $membershipUuid ],
                    'person_full_name_or_person_emails_address_cont'     => $searchTerm,
                    'active_at'                                          => 'now',
                ],
            ];

            try {
                $client   = wicket_api_client();
                $response = $client->post(
                    '/person_memberships/query?' . http_build_query( $queryArgs ),
                    [ 'json' => $payload ]
                );

                $normalized = $this->normalizeMembershipResponse( $response );
                if ( null !== $normalized ) {
                    return $normalized;
                }
            } catch ( \Throwable $searchException ) {
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
            'filter[active_at]' => 'now',
        ];

        if ( '' !== $searchTerm ) {
            $queryParams['filter[search]'] = $searchTerm;
        }

        if ( function_exists( 'wicket_api_client' ) ) {
            try {
                $client   = wicket_api_client();
                $endpoint = '/organization_memberships/' . rawurlencode( $membershipUuid ) . '/person_memberships';
                $response = $client->get( $endpoint . '?' . http_build_query( $queryParams ) );

                $normalized = $this->normalizeMembershipResponse( $response );
                if ( null !== $normalized ) {
                    // Cache initial load only (no search term)
                    if (empty($searchTerm)) {
                        $cache_key = 'orgman_members_initial_' . md5($membershipUuid . '_' . $page . '_' . $size);
                        $this->set_cached_data($cache_key, $normalized);
                    }
                    return $normalized;
                }

            } catch ( \Throwable $e ) {
                $logger->error(
                    '[OrgMan] Error fetching organization membership members: ' . $e->getMessage(),
                    [
                        'source'          => 'wicket-orgman',
                        'membership_uuid' => $membershipUuid,
                    ]
                );
            }
        }

        $response = $this->membership_service()->get_org_membership_members( $membershipUuid, $args );
        if ( is_wp_error( $response ) ) {
            /** @var \WP_Error $response */
            $error_message = $response->get_error_message();
            wc_get_logger()->error(
                '[OrgMan] MembershipService::get_org_membership_members() returned error',
                [
                    'source'          => 'wicket-orgman',
                    'membership_uuid' => $membershipUuid,
                    'error'           => $error_message,
                ]
            );
            return null;
        }
        $final_response = $this->normalizeMembershipResponse( $response );

        // Cache initial load only (no search term)
        if (empty($searchTerm) && null !== $final_response) {
            $cache_key = 'orgman_members_initial_' . md5($membershipUuid . '_' . $page . '_' . $size);
            $this->set_cached_data($cache_key, $final_response);
        }

        return $final_response;
    }

    /**
     * Clear the cached member list for a specific organization membership.
     *
     * @param string $membershipUuid The membership UUID.
     * @return void
     */
    public function clear_members_cache( string $membershipUuid ): void {
        if ( empty( $membershipUuid ) ) {
            return;
        }
        // Clear first 5 pages of cache for typical sizes
        $sizes = [ 10, 15, 20, 50, 100 ];
        for ( $p = 1; $p <= 5; $p++ ) {
            foreach ( $sizes as $s ) {
                $cache_key = 'orgman_members_initial_' . md5( $membershipUuid . '_' . $p . '_' . $s );
                delete_transient( $cache_key );
            }
        }
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
    public function get_members( string $membershipUuid, string $orgUuid, array $args = [] ): array {
        $page = max( 1, (int) ( $args['page'] ?? 1 ) );
        $size = max( 1, (int) ( $args['size'] ?? 15 ) );
        $query = isset( $args['query'] ) ? sanitize_text_field( (string) $args['query'] ) : '';

        $membersResponse = $this->getMembershipMembers(
            $membershipUuid,
            [
                'page'  => $page,
                'size'  => $size,
                'query' => $query ?: null,
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
            ]
        );
    }

    /**
     * Retrieve group roster members for groups strategy.
     *
     * @param string $group_uuid Group identifier.
     * @param string $org_identifier Organization identifier for filtering.
     * @param array $args Optional args (page, size, query).
     * @return array
     */
    public function get_group_members( string $group_uuid, string $org_identifier, array $args = [] ): array {
        $page = max( 1, (int) ( $args['page'] ?? 1 ) );
        $size = max( 1, (int) ( $args['size'] ?? 15 ) );
        $query = isset( $args['query'] ) ? sanitize_text_field( (string) $args['query'] ) : '';

        $group_service = new \OrgManagement\Services\GroupService();

        return $group_service->get_group_members( $group_uuid, $org_identifier, [
            'page' => $page,
            'size' => $size,
            'query' => $query,
        ] );
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
    public function search_members( string $membershipUuid, string $orgUuid, string $search, array $args = [] ): array {
        $args['query'] = $search;
        return $this->get_members( $membershipUuid, $orgUuid, $args );
    }

    /**
     * Structure the members response for templates and partials.
     *
     * @param array|null $membersResponse Raw response from API/helpers.
     * @param array      $context         Context values: org_uuid, membership_uuid, page, size, query.
     * @return array
     */
    private function prepareMembersResult( ?array $membersResponse, array $context ): array {
        $logger = wc_get_logger();

        $page = max( 1, (int) ( $context['page'] ?? 1 ) );
        $size = max( 1, (int) ( $context['size'] ?? 15 ) );
        $query = isset( $context['query'] ) ? (string) $context['query'] : '';
        $orgUuid = (string) ( $context['org_uuid'] ?? '' );
        $membershipUuid = $context['membership_uuid'] ?? null;

        $rawMembers = [];
        if ( is_array( $membersResponse ) ) {
            if ( isset( $membersResponse['data'] ) && is_array( $membersResponse['data'] ) ) {
                $rawMembers = $membersResponse['data'];
            } elseif ( isset( $membersResponse[0] ) ) {
                $rawMembers = $membersResponse;
            }
        }

        // Convert any stdClass objects in rawMembers to arrays
        $rawMembers = array_map( static function ( $member ) {
            if ( is_object( $member ) && ! is_array( $member ) ) {
                return json_decode( json_encode( $member ), true );
            }
            return $member;
        }, $rawMembers );

        $ownerId = null;
        if ( ! empty( $membershipUuid ) ) {
            try {
                $membershipService = new \OrgManagement\Services\MembershipService();
                $membershipData    = $membershipService->getOrgMembershipData( (string) $membershipUuid );
                if ( is_array( $membershipData ) ) {
                    $ownerId = $membershipData['data']['relationships']['owner']['data']['id'] ?? null;
                }
            } catch ( \Throwable $e ) {
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
        if ( is_array( $membersResponse ) && isset( $membersResponse['included'] ) && is_array( $membersResponse['included'] ) ) {
            foreach ( $membersResponse['included'] as $included ) {
                // Convert stdClass objects to arrays
                if ( is_object( $included ) && ! is_array( $included ) ) {
                    $included = json_decode( json_encode( $included ), true );
                }

                if ( ( $included['type'] ?? '' ) === 'people' && isset( $included['id'] ) ) {
                    $peopleIndex[ $included['id'] ] = $included;
                }
            }
        }

        $members = [];

        foreach ( $rawMembers as $member ) {
            // Convert stdClass objects to arrays
            if ( is_object( $member ) && ! is_array( $member ) ) {
                $member = json_decode( json_encode( $member ), true );
            }

            if ( ! is_array( $member ) ) {
                continue;
            }

            $memberAttributes = $member['attributes'] ?? [];

            $personUuid = $member['relationships']['person']['data']['id']
                ?? $member['person']['id']
                ?? null;

            $personData = ( $personUuid && isset( $peopleIndex[ $personUuid ] ) ) ? $peopleIndex[ $personUuid ] : null;
            $personAttributes = $personData['attributes'] ?? [];

            if ( ! $personData && $personUuid ) {
                try {
                    $person = $this->get_person_by_id( $personUuid );
                    if ( is_array( $person ) && isset( $person['data']['attributes'] ) ) {
                        $personData = $person;
                        $personAttributes = $person['data']['attributes'];
                    }
                } catch ( \Throwable $e ) {
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
            if ( $personUuid ) {
                try {
                    $rolesList = $this->getPersonCurrentRolesByOrgId( $personUuid, $orgUuid );
                    if ( is_array( $rolesList ) ) {
                        $currentRolesList = array_values( array_filter( array_map( 'strval', $rolesList ) ) );
                    }
                } catch ( \Throwable $e ) {
                    $logger->warning(
                        '[OrgMan] Failed to fetch person current roles',
                        [
                            'source'    => 'wicket-orgman',
                            'person_id' => $personUuid,
                            'org_id'    => $orgUuid,
                            'error'     => $e->getMessage(),
                        ]
                    );
                }
            }

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
            if ( ! empty( $memberAttributes['roles'] ) && is_array( $memberAttributes['roles'] ) ) {
                $roles = array_filter( array_map( 'strval', $memberAttributes['roles'] ) );
            } elseif ( ! empty( $memberAttributes['type'] ) ) {
                $roles = [ str_replace( '_', ' ', (string) $memberAttributes['type'] ) ];
            }

            $relationshipNames = [];
            $relationshipSlugs = [];
            $relationshipDescription = null;
            $personConnectionIds = []; // Store all connection IDs for this organization
            if ( $personUuid ) {
                try {
                    $connections = $this->connectionService()->getPersonConnectionsById( $personUuid );
                    if ( is_array( $connections ) && ! empty( $connections['data'] ) ) {
                        foreach ( $connections['data'] as $conn ) {
                            $orgId = $conn['relationships']['organization']['data']['id'] ?? null;
                            if ( $orgId !== $orgUuid ) {
                                continue;
                            }

                            $slug = $conn['attributes']['type'] ?? null;
                            $connId = $conn['attributes']['uuid'] ?? null;

                            if ( $slug ) {
                                $relationshipNames[] = ucwords( str_replace( '_', ' ', $slug ) );
                                $relationshipSlugs[] = $slug;
                            }

                            if ( $relationshipDescription === null ) {
                                $connDescription = $conn['attributes']['description'] ?? null;
                                if ( is_string( $connDescription ) && $connDescription !== '' ) {
                                    $relationshipDescription = $connDescription;
                                }
                            }

                            // Collect all connection IDs for this organization (like legacy system)
                            if ( $connId ) {
                                $personConnectionIds[] = $connId;
                            }
                        }

                        if ( in_array( 'Primary Contact', $relationshipNames, true ) ) {
                            $relationshipNames = array_values( array_diff( $relationshipNames, [ 'Primary Contact' ] ) );
                            array_unshift( $relationshipNames, 'Primary Contact' );
                        }
                    }
                } catch ( \Throwable $e ) {
                    $logger->warning(
                        '[OrgMan] Failed to fetch person connections',
                        [
                            'source'    => 'wicket-orgman',
                            'person_id' => $personUuid,
                            'error'     => $e->getMessage(),
                        ]
                    );
                }
            }

            // Filter by relationship type
            $allowedTypes = $this->config['relationships']['allowed_relationship_types'] ?? [];
            $excludedTypes = $this->config['relationships']['exclude_relationship_types'] ?? [];

            if ( ! empty( $allowedTypes ) ) {
                $hasAllowed = false;
                foreach ( $relationshipSlugs as $slug ) {
                    if ( in_array( $slug, $allowedTypes, true ) ) {
                        $hasAllowed = true;
                        break;
                    }
                }
                if ( ! $hasAllowed ) {
                    continue;
                }
            }

            if ( ! empty( $excludedTypes ) ) {
                foreach ( $relationshipSlugs as $slug ) {
                    if ( in_array( $slug, $excludedTypes, true ) ) {
                        continue 2;
                    }
                }
            }

            $confirmedAt = $personData['data']['attributes']['user']['confirmed_at'] ??
                ( $personData['user']['confirmed_at'] ??
                    ( $personAttributes['confirmed_at'] ??
                        ( $memberAttributes['confirmed_at'] ?? null ) ) );

            $members[] = [
                'person_uuid'           => $personUuid,
                'first_name'            => $firstName,
                'last_name'             => $lastName,
                'full_name'             => trim( $firstName . ' ' . $lastName ),
                'title'                 => $title,
                'email'                 => $email,
                'roles'                 => $roles,
                'current_roles'         => $currentRolesList,
                'confirmed_at'          => $confirmedAt,
                'status'                => $personAttributes['status'] ?? null,
                'job_level'             => $personAttributes['job_level'] ?? null,
                'relationship_names'    => ! empty( $relationshipNames ) ? implode( ', ', $relationshipNames ) : null,
                'relationship_type'     => ! empty( $relationshipSlugs ) ? reset($relationshipSlugs) : null,
                'relationship_description' => $relationshipDescription,
                'is_owner'              => ( ! empty( $ownerId ) && $personUuid && $personUuid === $ownerId ),
                'person_connection_ids' => ! empty( $personConnectionIds ) ? implode( ',', $personConnectionIds ) : null, // All connection IDs for this org (like legacy)
                'person_membership_id'  => $member['id'] ?? null,
            ];
        }

        $totalItems = 0;
        if ( is_array( $membersResponse ) ) {
            if ( isset( $membersResponse['meta']['page']['total_items'] ) ) {
                $totalItems = (int) $membersResponse['meta']['page']['total_items'];
            } elseif ( isset( $membersResponse['meta']['total'] ) ) {
                $totalItems = (int) $membersResponse['meta']['total'];
            }
        }

        if ( 0 === $totalItems ) {
            $totalItems = count( $members );
        }

        $totalPages = (int) max( 1, ceil( $totalItems / max( 1, $size ) ) );

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
    private function normalizeMembershipResponse( $response ): ?array {
        if ( is_array( $response ) ) {
            return $response;
        }

        $body = null;

        if ( $response instanceof \Psr\Http\Message\ResponseInterface ) {
            $body = (string) $response->getBody();
        } elseif ( is_object( $response ) && method_exists( $response, 'body' ) ) {
            $body = (string) $response->body();
        } elseif ( is_object( $response ) && method_exists( $response, 'getBody' ) ) {
            $body = (string) $response->getBody();
        } elseif ( is_string( $response ) ) {
            $body = $response;
        }

        if ( null === $body ) {
            wc_get_logger()->debug(
                '[OrgMan] Membership response had no body to decode',
                [
                    'source'   => 'wicket-orgman',
                    'respType' => is_object( $response ) ? get_class( $response ) : gettype( $response ),
                ]
            );
            return null;
        }

        $decoded = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wc_get_logger()->warning(
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
     * Get current roles for a person in a specific organization using MDP API
     *
     * @param string $personUuid The person UUID
     * @param string $orgUuid The organization UUID
     * @return array Array of role names
     */
    public function getPersonCurrentRolesByOrgId($personUuid, $orgUuid)
    {
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
                'sort' => '-global,name'
            ]
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

            return $roles;
        }

        return [];
    }

    /**
     * Get formatted roles string for display
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
     * Check if a user is confirmed by their UUID
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
            // Get person data directly from API
            $person = $this->get_person_by_id($personUuid);

            if (!is_array($person) || !isset($person['data']['attributes'])) {
                return false;
            }

            // Check confirmation status using the same logic as member display
            $confirmedAt = $person['data']['attributes']['user']['confirmed_at'] ?? null;

            // User is confirmed if confirmed_at is not null and not empty
            return !empty($confirmedAt);

        } catch (\Throwable $e) {
            // Log error for debugging but return false for safety
            $logger = wc_get_logger();
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
     * Get confirmation status of current user
     *
     * @return bool True if current user is confirmed, false otherwise
     */
    public function isCurrentUserConfirmed(): bool
    {
        return $this->isUserConfirmed();
    }

    /**
     * Check if user is confirmed by UUID (alias for isUserConfirmed with current user fallback)
     *
     * @param string|null $personUuid The person UUID. If null or empty, checks current user.
     * @return bool True if the user is confirmed, false if not or if user not found
     */
    public function checkUserConfirmation(?string $personUuid = null): bool
    {
        return $this->isUserConfirmed($personUuid);
    }

    /**
     * Get person data by UUID using the Wicket API
     *
     * @param string $personUuid The person UUID
     * @return array|null Person data or null on failure
     */
    public function get_person_by_id($personUuid)
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
    public function update_member_roles($personUuid, $orgUuid, $membershipUuid, $roles)
    {
        if (!function_exists('wicket_api_client')) {
            return new \WP_Error('api_unavailable', 'Wicket API client is not available.');
        }

        if (empty($personUuid) || empty($orgUuid) || empty($membershipUuid)) {
            return new \WP_Error('invalid_params', 'Person UUID, organization UUID, and membership UUID are required.');
        }

        try {
            $client = wicket_api_client();

            // Get current person memberships
            $memberships_endpoint = '/organization_memberships/' . rawurlencode($membershipUuid) . '/person_memberships';
            $response = $client->get($memberships_endpoint . '?page[number]=1&page[size]=100&include=person');

            if (empty($response['data']) || !is_array($response['data'])) {
                return new \WP_Error('membership_not_found', 'Person membership not found in this organization.');
            }

            // Find the person's membership
            $person_membership = null;
            foreach ($response['data'] as $membership) {
                $currentPersonId = $membership['relationships']['person']['data']['id'] ?? null;
                if ($currentPersonId === $personUuid) {
                    $person_membership = $membership;
                    break;
                }
            }

            if (!$person_membership) {
                return new \WP_Error('membership_not_found', 'Person membership not found in this organization.');
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

            // Get current person data to find existing role IDs
            $person_data = $this->get_person_by_id($personUuid);
            if (is_wp_error($person_data)) {
                return new \WP_Error('person_not_found', 'Unable to retrieve person data');
            }

            // Build a map of current role IDs for lookup
            $current_role_ids = [];
            if (!empty($person_data['included'])) {
                foreach ($person_data['included'] as $included) {
                    if ($included['type'] === 'roles') {
                        $role_name = $included['attributes']['name'] ?? '';
                        if ($role_name) {
                            $current_role_ids[$role_name] = $included['id'];
                        }
                    }
                }
            }

            // Define which roles we can manage (organization-specific roles only)
            $manageable_roles = ['membership_manager', 'org_editor'];

            // Only consider manageable roles for add/remove operations
            $desired_manageable_roles = array_intersect($roles, $manageable_roles);
            $current_manageable_roles = array_intersect(array_keys($current_role_ids), $manageable_roles);

            // Determine which manageable roles to add and which to remove
            $roles_to_add = array_diff($desired_manageable_roles, $current_manageable_roles);
            $roles_to_remove = array_diff($current_manageable_roles, $desired_manageable_roles);

            // Remove roles that are no longer needed
            foreach ($roles_to_remove as $role_name) {
                if (isset($current_role_ids[$role_name])) {
                    $role_id = $current_role_ids[$role_name];
                    $remove_payload = [
                        'data' => [
                            [
                                'type' => 'roles',
                                'id' => $role_id
                            ]
                        ]
                    ];

                    try {
                        $client->delete("people/$personUuid/relationships/roles", ['json' => $remove_payload]);
                    } catch (\Exception $e) {
                        error_log("[OrgMan] Failed to remove role '$role_name' from person $personUuid: " . $e->getMessage());
                        // Continue with other roles even if one fails
                    }
                }
            }

            // Add new roles
            foreach ($roles_to_add as $role_name) {
                $add_payload = [
                    'data' => [
                        'type' => 'roles',
                        'attributes' => [
                            'name' => $role_name,
                        ]
                    ]
                ];

                // Include organization context if provided
                if (!empty($orgUuid)) {
                    $add_payload['data']['relationships']['resource']['data'] = [
                        'id' => $orgUuid,
                        'type' => 'organizations'
                    ];
                }

                try {
                    $client->post("people/$personUuid/roles", ['json' => $add_payload]);
                } catch (\Exception $e) {
                    error_log("[OrgMan] Failed to add role '$role_name' to person $personUuid: " . $e->getMessage());
                    // Continue with other roles even if one fails
                }
            }

            // Get person data for response
            $person_data = $this->get_person_by_id($personUuid);
            return [
                'success' => true,
                'first_name' => $person_data['data']['attributes']['first_name'] ?? '',
                'last_name' => $person_data['data']['attributes']['last_name'] ?? '',
                'roles' => $roles,
            ];

        } catch (\Exception $e) {
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
    public function update_member_relationship($personUuid, $orgUuid, $relationshipType)
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
            $config = \OrgManagement\Config\get_config();
            $relationship_based_permissions = $config['permissions']['relationship_based_permissions'] ?? false;

            if ($relationship_based_permissions) {
                // Get the role mapping for this relationship type
                $relationship_roles_map = $config['permissions']['relationship_roles_map'] ?? [];
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
            $person_data = $this->get_person_by_id($personUuid);
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
    public function update_member_description($personUuid, $orgUuid, $description)
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
