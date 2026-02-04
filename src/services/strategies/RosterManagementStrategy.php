<?php
/**
 * Interface for Roster Management Strategies.
 *
 * @package OrgManagement
 */

namespace OrgManagement\Services\Strategies;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the contract for different roster management implementations.
 */
interface RosterManagementStrategy {
    /**
     * Add a member to an organization.
     *
     * @param string $org_id The organization ID.
     * @param array  $member_data Data for the new member.
     * @param array  $context Additional context for the operation (e.g., group_uuid).
     * @return array|\WP_Error Success or error response.
     */
    public function add_member( $org_id, $member_data, $context = [] );

    /**
     * Remove a member from an organization.
     *
     * @param string $org_id The organization ID.
     * @param string $person_uuid The UUID of the person to remove.
     * @param array  $context Additional context for the operation.
     * @return array|\WP_Error Success or error response.
     */
    public function remove_member( $org_id, $person_uuid, $context = [] );
}
