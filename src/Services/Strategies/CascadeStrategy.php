<?php

/**
 * Cascade Strategy for Roster Management.
 */

namespace OrgManagement\Services\Strategies;

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\ConnectionService;
use OrgManagement\Services\MembershipService;
use OrgManagement\Services\NotificationService;
use OrgManagement\Services\OrganizationService;
use OrgManagement\Services\PermissionService;
use OrgManagement\Services\PersonService;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Implements the cascade mode for roster management.
 */
class CascadeStrategy implements RosterManagementStrategy
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
     * @var OrganizationService|null
     */
    private $organizationService = null;

    /**
     * @var PersonService|null
     */
    private $personService = null;

    /**
     * @var MembershipService|null
     */
    private $membershipService = null;

    /**
     * @var NotificationService|null
     */
    private $notificationService = null;

    /**
     * @var ConfigService|null
     */
    private $configService = null;

    /**
     * @var \WC_Logger|null
     */
    private $logger = null;

    /**
     * Get person's current roles for a specific organization.
     *
     * @param string $person_uuid The UUID of the person
     * @param string $org_id The ID of the organization
     * @return array An array of current role slugs
     */
    private function get_person_current_roles_by_org_id($person_uuid, $org_id): array
    {
        if (!isset($this->permissionService)) {
            $this->permissionService = new PermissionService();
        }

        return $this->permissionService->get_person_current_roles_by_org_id($person_uuid, $org_id);
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
     * Lazily instantiate OrganizationService.
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

    /**
     * Lazily instantiate PersonService.
     *
     * @return PersonService
     */
    private function person_service(): PersonService
    {
        if (!isset($this->personService)) {
            $this->personService = new PersonService();
        }

        return $this->personService;
    }

    /**
     * Lazily instantiate MembershipService.
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
     * Add a member to an organization using the cascade method.
     *
     * @param string $org_id The organization ID.
     * @param array  $member_data Data for the new member.
     * @return array|\WP_Error
     */
    public function add_member($org_id, $member_data, $context = [])
    {
        $logger = $this->get_logger();
        $log_context = [
            'source' => 'wicket-orgman',
            'strategy' => 'cascade',
            'org_id' => $org_id,
            'member_email' => $member_data['email'] ?? null,
        ];

        try {
            $logger->info('[OrgMan] Cascade strategy add_member invoked', $log_context);

            $required_functions = [
                'wicket_assign_role',
            ];
            foreach ($required_functions as $func) {
                if (!function_exists($func)) {
                    $logger->error('[OrgMan] Missing legacy dependency for cascade add_member', array_merge($log_context, [
                        'function' => $func,
                    ]));

                    return new \WP_Error('missing_function', "Legacy function {$func} not found.");
                }
            }

            $person_uuid = $this->person_service()->createOrGetPerson(
                $member_data['first_name'],
                $member_data['last_name'],
                $member_data['email'],
                []
            );

            if (is_wp_error($person_uuid)) {
                $logger->error('[OrgMan] Cascade strategy failed to create person', array_merge($log_context, [
                    'error' => $person_uuid->get_error_message(),
                ]));

                return $person_uuid;
            }
            $log_context['person_uuid'] = $person_uuid;
            $logger->debug('[OrgMan] Cascade strategy person resolved', $log_context);

            $membership_uuid = $this->membership_service()->get_current_person_memberships_by_organization($org_id);

            if (is_wp_error($membership_uuid)) {
                $logger->error('[OrgMan] Cascade strategy failed to locate membership uuid', array_merge($log_context, [
                    'error' => $membership_uuid->get_error_message(),
                ]));

                return $membership_uuid;
            }

            if (!$membership_uuid) {
                $logger->error('[OrgMan] Cascade strategy missing corporate membership for org', $log_context);

                return new \WP_Error('no_membership', 'Could not find a valid corporate membership for this organization.');
            }
            $log_context['membership_uuid'] = $membership_uuid;

            $has_membership = $this->connectionService()->personHasMembership($person_uuid, $membership_uuid);
            $config = \OrgManagement\Config\OrgManConfig::get();
            if (is_wp_error($has_membership) || !$has_membership) {
                $relationship_type = $context['relationship_type'] ?? $member_data['relationship_type'] ?? '';
                $relationship_type = is_string($relationship_type) ? sanitize_key($relationship_type) : '';
                $relationship_description = $context['relationship_description'] ?? $member_data['relationship_description'] ?? '';
                $relationship_description = is_string($relationship_description) ? sanitize_textarea_field($relationship_description) : '';
                $custom_types = $config['relationship_types']['custom_types'] ?? [];
                if ($relationship_type && !empty($custom_types) && !array_key_exists($relationship_type, $custom_types)) {
                    $relationship_type = '';
                }
                if (empty($relationship_type)) {
                    $relationship_type = \OrgManagement\Helpers\RelationshipHelper::get_default_relationship_type();
                }

                // Create person-to-organization connection
                $connection_payload = $this->connectionService()->buildConnectionPayload(
                    $person_uuid,
                    $org_id,
                    'person_to_organization',
                    $relationship_type,
                    $relationship_description
                );
                $connection_response = $this->connectionService()->createConnection($connection_payload);

                if (is_wp_error($connection_response)) {
                    $logger->error('[OrgMan] Cascade strategy failed to create connection', array_merge($log_context, [
                        'error' => $connection_response->get_error_message(),
                    ]));

                    return new \WP_Error('connection_failed', $connection_response->get_error_message() ?? 'Failed to create organization connection.');
                }
                $logger->debug('[OrgMan] Cascade strategy created org connection', $log_context);

                // Assign person to membership seat (CRITICAL STEP)
                $membership_assignment_result = $this->assign_person_to_membership_seat($person_uuid, $membership_uuid);
                if (is_wp_error($membership_assignment_result)) {
                    $logger->error('[OrgMan] Cascade membership assignment failed', array_merge($log_context, [
                        'error' => $membership_assignment_result->get_error_message(),
                    ]));

                    return $membership_assignment_result;
                }
                $logger->info('[OrgMan] Cascade membership assignment succeeded', $log_context);
            }

            // Get configuration for member addition settings
            $base_member_role = $config['member_addition']['base_member_role'] ?? 'member';
            $auto_assign_roles = $config['member_addition']['auto_assign_roles'] ?? [];

            // Assign base member role
            wicket_assign_role($person_uuid, $base_member_role, $org_id);
            $logger->debug('[OrgMan] Cascade base role assigned', array_merge($log_context, [
                'role' => $base_member_role,
            ]));

            // Assign auto-roles from config
            foreach ($auto_assign_roles as $role) {
                wicket_assign_role($person_uuid, $role, $org_id);
            }
            if (!empty($auto_assign_roles)) {
                $logger->debug('[OrgMan] Cascade auto roles assigned', array_merge($log_context, [
                    'roles' => $auto_assign_roles,
                ]));
            }

            // Handle additional roles from context (e.g., org_editor, membership_manager)
            $additional_roles = $context['roles'] ?? $member_data['roles'] ?? [];

            // Check for relationship-based permissions
            if (!empty($config['permissions']['relationship_based_permissions'])) {
                $relationship_type = $context['relationship_type'] ?? $member_data['relationship_type'] ?? '';
                $relationship_roles_map = $config['permissions']['relationship_roles_map'] ?? [];

                if ($relationship_type && isset($relationship_roles_map[$relationship_type])) {
                    $mapped_roles = $relationship_roles_map[$relationship_type];
                    if (is_array($mapped_roles)) {
                        $additional_roles = array_unique(array_merge($additional_roles, $mapped_roles));
                    }
                }
            }

            // Filter out membership_owner if configured to prevent assignment
            if (!empty($config['permissions']['prevent_owner_assignment'])) {
                $additional_roles = array_values(array_diff($additional_roles, ['membership_owner']));
            }

            if (!empty($additional_roles)) {
                foreach ($additional_roles as $role) {
                    wicket_assign_role($person_uuid, $role, $org_id);
                }
                $logger->debug('[OrgMan] Cascade context roles assigned', array_merge($log_context, [
                    'roles' => $additional_roles,
                ]));
            }

            $notification_result = $this->notification_service()->send_person_to_org_assignment_email($person_uuid, $org_id);
            if (is_wp_error($notification_result)) {
                $logger->error('[OrgMan] Cascade notification email failed', array_merge($log_context, [
                    'error' => $notification_result->get_error_message(),
                ]));
            } else {
                $logger->info('[OrgMan] Cascade notification email sent', $log_context);
            }

            $logger->info('[OrgMan] Cascade strategy member addition completed', $log_context);

            return [
                'status' => 'success',
                'message' => 'Member added successfully.',
                'person_uuid' => $person_uuid,
            ];

        } catch (\Exception $e) {
            $logger->error('[OrgMan] Cascade strategy add_member exception', array_merge($log_context, [
                'exception' => $e->getMessage(),
            ]));

            return new \WP_Error('add_member_exception', $e->getMessage());
        }
    }

    /**
     * Remove a member from an organization using the cascade method.
     *
     * @param string $org_id The organization ID.
     * @param string $person_uuid The UUID of the person to remove.
     * @param array  $context Additional context for the operation.
     * @return array|\WP_Error
     */
    public function remove_member($org_id, $person_uuid, $context = [])
    {
        $logger = $this->get_logger();
        $log_context = [
            'source' => 'wicket-orgman',
            'strategy' => 'cascade',
            'org_id' => $org_id,
            'person_uuid' => $person_uuid,
        ];

        try {
            $logger->info('[OrgMan] Cascade strategy remove_member invoked', $log_context);

            $required_functions = [];
            foreach ($required_functions as $func) {
                if (!function_exists($func)) {
                    $logger->error('[OrgMan] Cascade remove_member missing legacy dependency', array_merge($log_context, [
                        'function' => $func,
                    ]));

                    return new \WP_Error('missing_function', "Legacy function {$func} not found.");
                }
            }

            $org_owner = $this->organization_service()->get_organization_owner($org_id);
            if (!is_wp_error($org_owner) && $org_owner && $org_owner->uuid === $person_uuid) {
                $logger->warning('[OrgMan] Cascade remove_member attempted to remove owner', $log_context);

                return new \WP_Error('owner_removal_forbidden', 'The organization owner (Primary Member) cannot be removed.');
            }

            // Get person membership ID from context
            $person_membership_id = $context['person_membership_id'] ?? null;

            if (empty($person_membership_id)) {
                $logger->error('[OrgMan] Cascade remove_member missing person membership id', $log_context);

                return new \WP_Error('missing_person_membership_id', 'Person membership ID is required to remove a member.');
            }

            // End-date the person membership
            $result = $this->membership_service()->endPersonMembershipToday($person_membership_id);
            if (is_wp_error($result)) {
                $logger->error('[OrgMan] Cascade remove_member failed to end person membership', array_merge($log_context, [
                    'error' => $result->get_error_message(),
                ]));

                return $result;
            }

            // Remove all org-scoped roles
            $roles_to_remove = $this->get_person_current_roles_by_org_id($person_uuid, $org_id);
            if (!empty($roles_to_remove)) {
                $this->permission_service()->remove_person_roles_from_org($person_uuid, $roles_to_remove, $org_id);
                $logger->debug('[OrgMan] Cascade remove_member removed roles', array_merge($log_context, [
                    'roles_removed' => $roles_to_remove,
                ]));
            }

            $logger->info('[OrgMan] Cascade remove_member completed', $log_context);

            return ['status' => 'success', 'message' => 'Member removed successfully.'];

        } catch (\Exception $e) {
            $logger->error('[OrgMan] Cascade strategy remove_member exception', array_merge($log_context, [
                'exception' => $e->getMessage(),
            ]));

            return new \WP_Error('remove_member_exception', $e->getMessage());
        }
    }

    /**
     * Assign person to membership seat using Wicket API.
     *
     * @param string $person_uuid
     * @param string $membership_uuid
     * @return true|WP_Error
     */
    private function assign_person_to_membership_seat(string $person_uuid, string $membership_uuid)
    {
        if (!function_exists('wicket_assign_person_to_org_membership')) {
            return new \WP_Error('missing_dependency', 'Membership assignment helper is unavailable.');
        }

        try {
            // Get organization membership data to pass to the assignment function
            $membership_data = $this->membership_service()->getOrgMembershipData($membership_uuid);
            if (is_wp_error($membership_data)) {
                return $membership_data;
            }

            // Extract Membership Type ID
            $membership_type_id = $membership_data['data']['relationships']['membership']['data']['id'] ?? '';
            if (empty($membership_type_id)) {
                return new \WP_Error('membership_type_missing', 'Could not find membership type ID.');
            }

            // Assign the person to the membership seat
            $result = wicket_assign_person_to_org_membership(
                $person_uuid,           // person ID
                $membership_type_id,    // membership type ID
                $membership_uuid,       // organization membership ID
                $membership_data        // organization membership data
            );

            if (!$result) {
                return new \WP_Error('membership_assignment_failed', 'Failed to assign person to membership seat.');
            }

            return true;

        } catch (\Throwable $e) {
            return new \WP_Error('membership_assignment_exception', $e->getMessage());
        }
    }

    /**
     * Lazily instantiate NotificationService.
     *
     * @return NotificationService
     */
    private function notification_service(): NotificationService
    {
        if (!isset($this->notificationService)) {
            $this->notificationService = new NotificationService();
        }

        return $this->notificationService;
    }

    /**
     * Lazily instantiate ConfigService.
     *
     * @return ConfigService
     */
    private function config_service(): ConfigService
    {
        if (!isset($this->configService)) {
            $this->configService = new ConfigService();
        }

        return $this->configService;
    }

    /**
     * Lazily instantiate PermissionService.
     *
     * @return PermissionService
     */
    private function permission_service(): PermissionService
    {
        if (!isset($this->permissionService)) {
            $this->permissionService = new PermissionService();
        }

        return $this->permissionService;
    }

    /**
     * Retrieve shared logger instance.
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
