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
    private function getPersonCurrentRolesByOrgId($person_uuid, $org_id): array
    {
        if (!isset($this->permissionService)) {
            $this->permissionService = new PermissionService();
        }

        return $this->permissionService->getPersonCurrentRolesByOrgId($person_uuid, $org_id);
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
    private function organizationService(): OrganizationService
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
    private function personService(): PersonService
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
    private function membershipService(): MembershipService
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
    public function addMember($org_id, $member_data, $context = [])
    {
        $logger = $this->getLogger();
        $log_context = [
            'source' => 'wicket-orgman',
            'strategy' => 'cascade',
            'org_id' => $org_id,
            'member_email' => $member_data['email'] ?? null,
        ];

        try {
            $logger->info('[OrgMan] Cascade strategy add_member invoked', $log_context);

            $required_functions = [
                'wicket_assignRole',
            ];
            foreach ($required_functions as $func) {
                if (!function_exists($func)) {
                    $logger->error('[OrgMan] Missing legacy dependency for cascade add_member', array_merge($log_context, [
                        'function' => $func,
                    ]));

                    return new \WP_Error('missing_function', "Legacy function {$func} not found.");
                }
            }

            $person_uuid = $this->personService()->createOrGetPerson(
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

            $membership_uuid = $this->membershipService()->getCurrentPersonMembershipsByOrganization($org_id);

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
            if (is_wp_error($has_membership)) {
                $logger->error('[OrgMan] Cascade membership lookup failed', array_merge($log_context, [
                    'error' => $has_membership->get_error_message(),
                ]));

                return $has_membership;
            }

            if (!$has_membership) {
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

                $has_relationship = $this->connectionService()->personHasRelationship($person_uuid, $org_id);
                if (is_wp_error($has_relationship)) {
                    return $has_relationship;
                }

                if (!$has_relationship) {
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
                }

                // Assign person to membership seat (CRITICAL STEP)
                $membership_assignment_result = $this->assignPersonToMembershipSeat($person_uuid, $membership_uuid);
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
            $auto_assignRoles = $config['member_addition']['auto_assignRoles'] ?? [];

            // Assign base member role
            wicket_assignRole($person_uuid, $base_member_role, $org_id);
            $logger->debug('[OrgMan] Cascade base role assigned', array_merge($log_context, [
                'role' => $base_member_role,
            ]));

            // Assign auto-roles from config
            foreach ($auto_assignRoles as $role) {
                wicket_assignRole($person_uuid, $role, $org_id);
            }
            if (!empty($auto_assignRoles)) {
                $logger->debug('[OrgMan] Cascade auto roles assigned', array_merge($log_context, [
                    'roles' => $auto_assignRoles,
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
                    wicket_assignRole($person_uuid, $role, $org_id);
                }
            }

            $notification_result = $this->notificationService()->sendPersonToOrgAssignmentEmail($person_uuid, $org_id);
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

    public function removeMember($org_id, $person_uuid, $context = [])
    {
        $logger = $this->getLogger();
        $log_context = [
            'source' => 'wicket-orgman',
            'strategy' => 'cascade',
            'org_id' => $org_id,
            'person_uuid' => $person_uuid,
        ];

        try {
            $person_membership_id = sanitize_text_field((string) ($context['person_membership_id'] ?? ''));

            if ('' === $person_membership_id) {
                return new \WP_Error('missing_person_membership_id', 'Person membership ID is required to remove a member.');
            }

            if (!empty($org_id)) {
                $org_owner = $this->organizationService()->getOrganizationOwner($org_id);
                if (!is_wp_error($org_owner) && $org_owner && $org_owner->uuid === $person_uuid) {
                    return new \WP_Error('owner_removal_forbidden', 'The organization owner (Primary Member) cannot be removed.');
                }
            }

            $remove_result = $this->membershipService()->endPersonMembershipToday($person_membership_id);
            if (is_wp_error($remove_result)) {
                $logger->error('[OrgMan] Cascade strategy failed to end person membership', array_merge($log_context, [
                    'error' => $remove_result->get_error_message(),
                ]));

                return $remove_result;
            }

            $roles_to_remove = $this->permissionService()->getPersonCurrentRolesByOrgId($person_uuid, $org_id);
            if (!empty($roles_to_remove)) {
                $roles_result = $this->permissionService()->removePersonRolesFromOrg($person_uuid, $roles_to_remove, $org_id);
                if (is_wp_error($roles_result)) {
                    return $roles_result;
                }
            }

            $logger->info('[OrgMan] Cascade strategy removed member successfully', $log_context);

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
    private function assignPersonToMembershipSeat(string $person_uuid, string $membership_uuid)
    {
        $logger = $this->getLogger();
        $log_context = [
            'source' => 'wicket-orgman',
            'strategy' => 'cascade',
            'person_uuid' => $person_uuid,
            'membership_uuid' => $membership_uuid,
        ];

        if (!function_exists('wicket_assign_person_to_org_membership')) {
            return new \WP_Error('missing_dependency', 'Membership assignment helper is unavailable.');
        }

        try {
            $already_assigned = $this->connectionService()->personHasMembership($person_uuid, $membership_uuid);
            if (is_wp_error($already_assigned)) {
                return $already_assigned;
            }

            if ($already_assigned) {
                return true;
            }

            // Get organization membership data to pass to the assignment function
            $membership_data = $this->membershipService()->getOrgMembershipData($membership_uuid);
            if (is_wp_error($membership_data)) {
                return $membership_data;
            }

            if (empty($membership_data) || empty($membership_data['data'])) {
                return new \WP_Error('membership_data_missing', 'Membership details unavailable.');
            }

            // Extract Membership Type ID
            $membership_type_id = $membership_data['data']['relationships']['membership']['data']['id'] ?? '';
            if (empty($membership_type_id) && !empty($membership_data['included']) && is_array($membership_data['included'])) {
                foreach ($membership_data['included'] as $included) {
                    $included_type = $included['type'] ?? '';
                    if (in_array($included_type, ['memberships', 'membership', 'membership_types'], true)) {
                        $membership_type_id = $included['id'] ?? '';
                        if (!empty($membership_type_id)) {
                            break;
                        }
                    }
                }
            }

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

            if (empty($result) || isset($result['errors'])) {
                $error_message = $result['errors'][0]['detail'] ?? 'Failed to assign person to membership seat.';
                $logger->warning('[OrgMan] Cascade membership assignment API returned error, verifying existing membership', array_merge($log_context, [
                    'membership_type_id' => $membership_type_id,
                    'api_error' => $error_message,
                ]));

                $post_check = $this->connectionService()->personHasMembership($person_uuid, $membership_uuid);
                if (true === $post_check) {
                    $logger->info('[OrgMan] Cascade membership assignment already present after API error', $log_context);

                    return true;
                }

                if (is_wp_error($post_check)) {
                    $logger->error('[OrgMan] Cascade membership verification after API error failed', array_merge($log_context, [
                        'verification_error' => $post_check->get_error_message(),
                    ]));
                }

                return new \WP_Error('membership_assignment_failed', $error_message);
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
    private function notificationService(): NotificationService
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
    private function configService(): ConfigService
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
    private function permissionService(): PermissionService
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
    private function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = wc_getLogger();
        }

        return $this->logger;
    }
}
