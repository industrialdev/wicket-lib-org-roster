<?php

/**
 * Membership Cycle Strategy for Roster Management.
 */

namespace OrgManagement\Services\Strategies;

use OrgManagement\Services\MembershipService;
use OrgManagement\Services\OrganizationService;
use WP_Error;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Implements cycle-scoped roster management where the target membership UUID is explicit.
 */
class MembershipCycleStrategy implements RosterManagementStrategy
{
    /**
     * @var DirectAssignmentStrategy|null
     */
    private $directStrategy = null;

    /**
     * @var MembershipService|null
     */
    private $membershipService = null;

    /**
     * @var OrganizationService|null
     */
    private $organizationService = null;

    /**
     * Add member via direct assignment, but scoped to explicit membership cycle.
     *
     * @param string $org_id
     * @param array  $member_data
     * @param array  $context
     * @return array|WP_Error
     */
    public function add_member($org_id, $member_data, $context = [])
    {
        $membership_uuid = $this->extract_membership_uuid($context);
        if ('' === $membership_uuid) {
            return new WP_Error('missing_membership_uuid', 'Membership UUID is required for membership_cycle strategy.');
        }

        if (!\OrgManagement\Helpers\PermissionHelper::can_add_members($org_id)) {
            return new WP_Error('no_permission', 'You do not have permission to add members to this organization.');
        }

        $scope_valid = $this->validate_membership_scope($org_id, $membership_uuid);
        if (is_wp_error($scope_valid)) {
            return $scope_valid;
        }

        $context['membership_uuid'] = $membership_uuid;

        return $this->direct_strategy()->add_member($org_id, $member_data, $context);
    }

    /**
     * Remove member by ending person membership assignment for the explicit cycle.
     *
     * @param string $org_id
     * @param string $person_uuid
     * @param array  $context
     * @return array|WP_Error
     */
    public function remove_member($org_id, $person_uuid, $context = [])
    {
        $membership_uuid = $this->extract_membership_uuid($context);
        if ('' === $membership_uuid) {
            return new WP_Error('missing_membership_uuid', 'Membership UUID is required for membership_cycle strategy.');
        }

        $person_membership_id = sanitize_text_field((string) ($context['person_membership_id'] ?? ''));
        if ('' === $person_membership_id) {
            return new WP_Error('missing_person_membership_id', 'Person membership ID is required.');
        }

        if (!\OrgManagement\Helpers\PermissionHelper::can_remove_members($org_id)) {
            return new WP_Error('no_permission', 'You do not have permission to remove members from this organization.');
        }

        $scope_valid = $this->validate_membership_scope($org_id, $membership_uuid);
        if (is_wp_error($scope_valid)) {
            return $scope_valid;
        }

        $cycle_config = \OrgManagement\Config\get_config()['membership_cycle'] ?? [];
        $prevent_owner_removal = (bool) ($cycle_config['permissions']['prevent_owner_removal'] ?? true);
        if ($prevent_owner_removal) {
            $org_owner = $this->organization_service()->get_organization_owner($org_id);
            if (!is_wp_error($org_owner) && $org_owner && $org_owner->uuid === $person_uuid) {
                return new WP_Error('owner_removal_forbidden', 'The organization owner (Primary Member) cannot be removed.');
            }
        }

        $remove_result = $this->membership_service()->endPersonMembershipToday($person_membership_id);
        if (is_wp_error($remove_result)) {
            return $remove_result;
        }

        return ['status' => 'success', 'message' => 'Member removed successfully.'];
    }

    /**
     * Extract sanitized membership UUID from context.
     *
     * @param array $context
     * @return string
     */
    private function extract_membership_uuid(array $context): string
    {
        $membership_uuid = $context['membership_uuid'] ?? $context['membership_id'] ?? '';

        return sanitize_text_field((string) $membership_uuid);
    }

    /**
     * Validate membership UUID belongs to organization.
     *
     * @param string $org_id
     * @param string $membership_uuid
     * @return true|WP_Error
     */
    private function validate_membership_scope($org_id, string $membership_uuid)
    {
        $org_id = sanitize_text_field((string) $org_id);
        if ('' === $org_id) {
            return new WP_Error('invalid_org_id', 'Organization identifier is required.');
        }

        $membership_data = $this->membership_service()->getOrgMembershipData($membership_uuid);
        if (empty($membership_data) || !is_array($membership_data)) {
            return new WP_Error('invalid_membership_uuid', 'Membership UUID is invalid or unavailable.');
        }

        $membership_org_id = $membership_data['data']['relationships']['organization']['data']['id'] ?? '';
        if ('' !== $membership_org_id && $membership_org_id !== $org_id) {
            return new WP_Error('membership_org_mismatch', 'Membership does not belong to the selected organization.');
        }

        return true;
    }

    /**
     * Lazily instantiate direct strategy.
     *
     * @return DirectAssignmentStrategy
     */
    private function direct_strategy(): DirectAssignmentStrategy
    {
        if (!isset($this->directStrategy)) {
            $this->directStrategy = new DirectAssignmentStrategy();
        }

        return $this->directStrategy;
    }

    /**
     * Lazily instantiate membership service.
     *
     * @return MembershipService
     */
    private function membership_service(): MembershipService
    {
        if (!isset($this->membershipService)) {
            $this->membershipService = new MembershipService();
        }

        return $this->membershipService;
    }

    /**
     * Lazily instantiate organization service.
     *
     * @return OrganizationService
     */
    private function organization_service(): OrganizationService
    {
        if (!isset($this->organizationService)) {
            $this->organizationService = new OrganizationService();
        }

        return $this->organizationService;
    }
}
