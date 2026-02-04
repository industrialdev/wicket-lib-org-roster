<?php
/**
 * Membership Service for Org Management
 */

namespace OrgManagement\Services;

use OrgManagement\Services\ConnectionService;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MembershipService {
    /**
     * @var ConnectionService|null
     */
    private $connectionService = null;

    /**
     * @var array
     */
    private $config;

    public function __construct() {
        $this->config = \OrgManagement\Config\get_config();
    }

    /**
     * Retrieve all organization memberships for an organization.
     *
     * @param string $organizationUuid Organization identifier.
     * @return array<int, array>
     */
    public function getOrganizationMemberships( string $organizationUuid ): array {
        if ( empty( $organizationUuid ) || ! function_exists( 'wicket_get_org_memberships' ) ) {
            return [];
        }

        try {
            $memberships = wicket_get_org_memberships( $organizationUuid );
        } catch ( \Throwable $e ) {
            wc_get_logger()->error(
                '[OrgMan] Failed fetching organization memberships: ' . $e->getMessage(),
                [ 'source' => 'wicket-orgman', 'org_uuid' => $organizationUuid ]
            );
            return [];
        }

        return is_array( $memberships ) ? $memberships : [];
    }

    /**
     * Resolve the active (or fallback) organization membership UUID for an organization.
     *
     * @param string $organizationUuid Organization identifier.
     * @return string|null
     */
    public function getOrganizationMembershipUuid( string $organizationUuid ): ?string {
        $memberships = $this->getOrganizationMemberships( $organizationUuid );
        if ( empty( $memberships ) ) {
            return null;
        }

        $fallback = null;
        foreach ( $memberships as $membership ) {
            $uuid = $membership['membership']['attributes']['uuid']
                ?? $membership['membership']['id']
                ?? null;

            if ( empty( $uuid ) ) {
                continue;
            }

            $isActive = (bool) ( $membership['membership']['attributes']['active'] ?? false );
            $inGrace = (bool) ( $membership['membership']['attributes']['in_grace'] ?? false );

            if ( $isActive || $inGrace ) {
                return $uuid;
            }

            if ( null === $fallback ) {
                $fallback = $uuid;
            }
        }

        return $fallback;
    }

    /**
     * Get the organization_membership UUID for the current user and an organization.
     * Prefers organization memberships when available; falls back to user-bound memberships.
     *
     * @param string $organizationUuid
     * @return string|null
     */
    public function getMembershipForOrganization( string $organizationUuid ): ?string {
        // Check cache first - use wicket UUID for consistency
        $current_user_uuid = wicket_current_person_uuid();
        $cache_key = 'orgman_membership_' . md5($current_user_uuid . '_' . $organizationUuid);
        $cached_data = false;

        // Only check cache if enabled
        if ($this->config['cache']['enabled'] ?? true) {
            $cached_data = get_transient($cache_key);
        }

        if (false !== $cached_data) {
            return $cached_data;
        }

        $resolved = $this->getOrganizationMembershipUuid( $organizationUuid );
        if ( $resolved ) {
            // Only cache if enabled
            if ($this->config['cache']['enabled'] ?? true) {
                $cache_duration = $this->config['cache']['duration'] ?? (5 * MINUTE_IN_SECONDS);
                set_transient($cache_key, $resolved, $cache_duration);
            }
            return $resolved;
        }

        if ( empty( $organizationUuid ) || ! function_exists( 'wicket_get_current_person_memberships' ) ) {
            // Only cache if enabled
            if ($this->config['cache']['enabled'] ?? true) {
                $cache_duration = $this->config['cache']['duration'] ?? (5 * MINUTE_IN_SECONDS);
                set_transient($cache_key, null, $cache_duration);
            }
            return null;
        }

        $memberships = wicket_get_current_person_memberships();
        if ( empty( $memberships['included'] ) || ! is_array( $memberships['included'] ) ) {
            // Only cache if enabled
            if ($this->config['cache']['enabled'] ?? true) {
                $cache_duration = $this->config['cache']['duration'] ?? (5 * MINUTE_IN_SECONDS);
                set_transient($cache_key, null, $cache_duration);
            }
            return null;
        }

        $fallback = null;
        foreach ( $memberships['included'] as $included ) {
            if (
                isset( $included['type'] ) && $included['type'] === 'organization_memberships' &&
                isset( $included['relationships']['organization']['data']['id'] ) &&
                $included['relationships']['organization']['data']['id'] === $organizationUuid
            ) {
                $isActive = $included['attributes']['active'] ?? null;
                if ( $isActive ) {
                    // Only cache if enabled
                    if ($this->config['cache']['enabled'] ?? true) {
                        $cache_duration = $this->config['cache']['duration'] ?? (5 * MINUTE_IN_SECONDS);
                        set_transient($cache_key, $included['id'], $cache_duration);
                    }
                    return $included['id'];
                }
                if ( ! $fallback ) {
                    $fallback = $included['id'];
                }
            }
        }

        // Only cache if enabled
        if ($this->config['cache']['enabled'] ?? true) {
            $cache_duration = $this->config['cache']['duration'] ?? (5 * MINUTE_IN_SECONDS);
            set_transient($cache_key, $fallback, $cache_duration);
        }
        return $fallback;
    }

    /**
     * Fetch organization membership details including the membership entity.
     *
     * @param string $membershipUuid
     * @return array|null
     */
    public function getOrgMembershipData( string $membershipUuid ): ?array {
        if ( empty( $membershipUuid ) ) {
            return null;
        }

        // Check cache first
        $cache_key = 'orgman_membership_data_' . md5($membershipUuid);
        $cached_data = false;

        // Only check cache if enabled
        if ($this->config['cache']['enabled'] ?? true) {
            $cached_data = get_transient($cache_key);
        }

        if (false !== $cached_data) {
            return $cached_data;
        }

        if ( ! function_exists( 'wicket_api_client' ) ) {
            // Only cache if enabled
            if ($this->config['cache']['enabled'] ?? true) {
                $cache_duration = $this->config['cache']['duration'] ?? (5 * MINUTE_IN_SECONDS);
                set_transient($cache_key, null, $cache_duration);
            }
            return null;
        }

        try {
            $client   = wicket_api_client();
            $endpoint = '/organization_memberships/' . rawurlencode( $membershipUuid ) . '?page[number]=1&sort=&include=membership%2Cowner';
            $response = $client->get( $endpoint );
            $data = isset( $response['data'] ) ? $response : null;

            // Cache the results using configured duration
            if ($this->config['cache']['enabled'] ?? true) {
                $cache_duration = $this->config['cache']['duration'] ?? (5 * MINUTE_IN_SECONDS);
                set_transient($cache_key, $data, $cache_duration);
            }

            return $data;
        } catch ( \Throwable $e ) {
            // Only cache if enabled
            if ($this->config['cache']['enabled'] ?? true) {
                $cache_duration = $this->config['cache']['duration'] ?? (5 * MINUTE_IN_SECONDS);
                set_transient($cache_key, null, $cache_duration);
            }
            return null;
        }
    }

    /**
     * Get current person's membership UUID for a specific organization.
     *
     * This method provides backward compatibility with the legacy function signature.
     *
     * @param string $organization_uuid The organization UUID.
     * @return string|WP_Error The membership UUID or WP_Error on failure.
     */
    public function get_current_person_memberships_by_organization( $organization_uuid ) {
        if ( empty( $organization_uuid ) ) {
            return new \WP_Error( 'invalid_params', 'Organization UUID is required.' );
        }

        try {
            $membership_uuid = $this->getMembershipForOrganization( $organization_uuid );

            if ( ! $membership_uuid ) {
                return new \WP_Error( 'no_membership', 'No membership found for this organization.' );
            }

            return $membership_uuid;

        } catch ( \Exception $e ) {
            error_log( "MembershipService::get_current_person_memberships_by_organization() - Exception: " . $e->getMessage() );
            return new \WP_Error( 'get_membership_exception', $e->getMessage() );
        }
    }

    /**
     * Search members for a specific membership.
     *
     * This method provides backward compatibility with the legacy function signature.
     * Searches for person memberships within a specific organization membership using query filters.
     *
     * @param string $membership_uuid The UUID of the membership to search within.
     * @param array $args Optional arguments containing page and size for pagination, and query for search.
     * @return array|WP_Error The search results or WP_Error on failure.
     */
    public function membership_search_members( $membership_uuid = '', $args = [] ) {
        if ( empty( $membership_uuid ) || empty( $args ) ) {
            return new \WP_Error( 'invalid_params', 'Membership UUID and arguments are required.' );
        }

        if ( ! function_exists( 'wicket_api_client' ) ) {
            return new \WP_Error( 'missing_dependency', 'Wicket API client is unavailable.' );
        }

        try {
            // Defaults
            $page = absint( isset( $args['page'] ) ? $args['page'] : 1 );
            $size = absint( isset( $args['size'] ) ? $args['size'] : 50 );
            $query = isset( $args['query'] ) ? sanitize_text_field( $args['query'] ) : '';

            if ( empty( $query ) ) {
                return new \WP_Error( 'invalid_query', 'Search query is required.' );
            }

            $client = wicket_api_client();

            // Build search filter parameters
            $filter_data = [
                'filter' => [
                    'organization_membership_uuid_in' => [ $membership_uuid ],
                    'person_full_name_or_person_emails_address_cont' => $query,
                    'active_at' => 'now'
                ]
            ];

            // Use POST request with proper filtering
            $response = $client->post( '/person_memberships/query?' . http_build_query( [
                'page[number]' => $page,
                'page[size]'   => $size,
                'include'      => 'emails,phones,addresses'
            ] ), [ 'json' => $filter_data ] );

            return isset( $response['data'] ) ? $response : new \WP_Error( 'no_results', 'No search results found.' );

        } catch ( \Exception $e ) {
            error_log( "MembershipService::membership_search_members() - Exception: " . $e->getMessage() );
            return new \WP_Error( 'search_failed', $e->getMessage() );
        }
    }

    /**
     * Get organization membership members.
     *
     * This method provides backward compatibility with the legacy function signature.
     * Retrieves person memberships for a specific organization membership with pagination support.
     *
     * @param string $membership_uuid The UUID of the organization membership.
     * @param array $args Optional arguments containing page and size for pagination.
     * @return array|WP_Error The membership members or WP_Error on failure.
     */
    public function get_org_membership_members( $membership_uuid = '', $args = [] ) {
        if ( empty( $membership_uuid ) ) {
            return new \WP_Error( 'invalid_params', 'Membership UUID is required.' );
        }

        if ( ! function_exists( 'wicket_api_client' ) ) {
            return new \WP_Error( 'missing_dependency', 'Wicket API client is unavailable.' );
        }

        try {
            // Defaults
            $page = absint( isset( $args['page'] ) ? $args['page'] : 1 );
            $size = absint( isset( $args['size'] ) ? $args['size'] : 20 );

            $client = wicket_api_client();
            $response = $client->get( '/organization_memberships/' . rawurlencode( $membership_uuid ) . '/person_memberships?page[number]=' . $page . '&page[size]=' . $size . '&filter[active_at]=now' );

            return isset( $response['data'] ) ? $response : new \WP_Error( 'no_results', 'No membership members found.' );

        } catch ( \Exception $e ) {
            error_log( "MembershipService::get_org_membership_members() - Exception: " . $e->getMessage() );
            return new \WP_Error( 'get_members_failed', $e->getMessage() );
        }
    }

    /**
     * End a person membership with today's date.
     *
     * @param string $person_membership_id The ID of the person membership to end-date.
     * @return array|WP_Error The updated person membership data or WP_Error on failure.
     */
    public function endPersonMembershipToday( $person_membership_id ) {
        if ( empty( $person_membership_id ) ) {
            return new \WP_Error( 'invalid_params', 'Person membership ID is required.' );
        }

        if ( ! function_exists( 'wicket_api_client' ) ) {
            return new \WP_Error( 'missing_dependency', 'Wicket API client is unavailable.' );
        }

        try {
            $client = wicket_api_client();

            // Get the current person membership
            $person_membership = $client->get( 'person_memberships/' . rawurlencode( $person_membership_id ) );
            if ( ! $person_membership || empty( $person_membership['data'] ) ) {
                return new \WP_Error( 'person_membership_not_found', 'Person membership not found.' );
            }

            // Prepare the update payload with end date set to today
            $person_membership_data = $person_membership['data'];
            $attributes = $person_membership_data['attributes'];

            // Set ends_at to today in site timezone
            $ends_at = ( new \DateTime( '@' . strtotime( date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) ), wp_timezone() ) )->format( 'Y-m-d\T00:00:00-05:01' );

            $update_payload = [
                'data' => [
                    'type'       => $person_membership_data['type'],
                    'id'         => $person_membership_id,
                    'attributes' => [
                        'ends_at' => $ends_at,
                    ]
                ]
            ];

            // Update the person membership
            $response = $client->patch( "person_memberships/{$person_membership_id}", [ 'json' => $update_payload ] );

            if ( ! empty( $response['errors'] ) ) {
                error_log( "MembershipService::endPersonMembershipToday() - API error: " . json_encode( $response['errors'] ) );
                return new \WP_Error( 'api_error', 'Failed to end-date person membership: ' . ( $response['errors'][0]['detail'] ?? 'Unknown error' ) );
            }

            return $response;

        } catch ( \Exception $e ) {
            error_log( "MembershipService::endPersonMembershipToday() - Exception: " . $e->getMessage() );
            return new \WP_Error( 'end_person_membership_exception', $e->getMessage() );
        }
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

}
