<?php

/**
 * Groups Strategy for Roster Management.
 */

namespace OrgManagement\Services\Strategies;

use OrgManagement\Services\ConnectionService;
use OrgManagement\Services\GroupService;
use OrgManagement\Services\NotificationService;
use OrgManagement\Services\OrganizationService;
use OrgManagement\Services\PersonService;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Implements the groups mode for roster management.
 */
class GroupsStrategy implements RosterManagementStrategy
{
    /**
     * @var ConnectionService|null
     */
    private $connectionService = null;

    /**
     * @var PersonService|null
     */
    private $personService = null;

    /**
     * @var NotificationService|null
     */
    private $notificationService = null;

    /**
     * @var OrganizationService|null
     */
    private $organizationService = null;

    /**
     * @var GroupService|null
     */
    private $groupService = null;

    /**
     * @var \WC_Logger|null
     */
    private $logger = null;

    public function add_member($org_id, $member_data, $context = [])
    {
        $logger = $this->get_logger();
        $log_context = [
            'source' => 'wicket-orgroster',
            'strategy' => 'groups',
            'org_id' => $org_id,
            'member_email' => $member_data['email'] ?? null,
            'group_uuid' => $context['group_uuid'] ?? null,
        ];

        try {
            $logger->info('[OrgRoster] Groups strategy add_member invoked', $log_context);

            if (empty($context['group_uuid'])) {
                $logger->error('[OrgRoster] Groups strategy missing group_uuid', $log_context);

                return new \WP_Error('missing_group_uuid', 'Group UUID is required for this operation.');
            }

            $group_uuid = $context['group_uuid'];
            $role_slug = sanitize_key((string) ($context['role'] ?? $context['roster_role'] ?? 'member'));
            $roster_roles = $this->group_service()->get_roster_roles();
            if (!in_array($role_slug, $roster_roles, true)) {
                $logger->warning('[OrgRoster] Groups strategy invalid roster role', array_merge($log_context, [
                    'role' => $role_slug,
                ]));

                return new \WP_Error('invalid_role', 'Invalid roster role for group membership.');
            }

            $current_person = wp_get_current_user();
            $manager_uuid = $current_person ? (string) $current_person->user_login : '';
            $manager_access = $this->group_service()->can_manage_group($group_uuid, $manager_uuid);
            if (empty($manager_access['allowed'])) {
                $logger->warning('[OrgRoster] Groups strategy access denied', array_merge($log_context, [
                    'manager_uuid' => $manager_uuid,
                ]));

                return new \WP_Error('no_group_access', 'You do not have permission to manage this group.');
            }

            $org_identifier = (string) ($manager_access['org_identifier'] ?? '');
            $org_uuid = (string) ($manager_access['org_uuid'] ?? $org_id);

            $person_uuid = $this->person_service()->createOrGetPerson(
                $member_data['first_name'],
                $member_data['last_name'],
                $member_data['email'],
                []
            );

            if (is_wp_error($person_uuid)) {
                $logger->error('[OrgRoster] Groups strategy failed to create/get person', array_merge($log_context, [
                    'error' => $person_uuid->get_error_message(),
                ]));

                return $person_uuid;
            }

            $log_context['person_uuid'] = $person_uuid;
            $logger->debug('[OrgRoster] Groups strategy resolved person', $log_context);

            if (!empty($org_uuid)) {
                $has_relationship = $this->connectionService()->personHasRelationship($person_uuid, $org_uuid);
                $config = \OrgManagement\Config\get_config();
                if (is_wp_error($has_relationship) || !$has_relationship) {
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

                    $logger->debug('[OrgRoster] Groups strategy creating org connection', $log_context);
                    $connection_payload = $this->connectionService()->buildConnectionPayload(
                        $person_uuid,
                        $org_uuid,
                        'person_to_organization',
                        $relationship_type,
                        $relationship_description
                    );
                    $connection_result = $this->connectionService()->createConnection($connection_payload);

                    if (is_wp_error($connection_result)) {
                        $logger->error('[OrgRoster] Groups strategy failed to create connection', array_merge($log_context, [
                            'error' => $connection_result->get_error_message(),
                        ]));

                        return new \WP_Error('connection_failed', $connection_result->get_error_message() ?? 'Failed to create organization connection.');
                    }
                }
            }

            $orgman_config = \OrgManagement\Config\get_config();
            $groups_config = is_array($orgman_config['groups'] ?? null) ? $orgman_config['groups'] : [];
            $seat_limited_roles = is_array($groups_config['seat_limited_roles'] ?? null)
                ? $groups_config['seat_limited_roles']
                : [$groups_config['member_role'] ?? 'member'];

            if (in_array($role_slug, $seat_limited_roles, true)) {
                $existing_members = $this->group_service()->get_group_members($group_uuid, $org_identifier, [
                    'page' => 1,
                    'size' => 50,
                    'query' => '',
                    'org_uuid' => $org_uuid,
                ]);
                foreach ($existing_members['members'] ?? [] as $member) {
                    if (sanitize_key((string) ($member['role'] ?? '')) === $role_slug) {
                        $logger->info('[OrgRoster] Groups strategy seat already occupied', array_merge($log_context, [
                            'role' => $role_slug,
                        ]));

                        return new \WP_Error('seat_unavailable', 'This group already has a member for your organization.');
                    }
                }
            }

            $logger->debug('[OrgRoster] Groups strategy adding member to group', array_merge($log_context, [
                'role' => $role_slug,
            ]));
            $custom_data_field = $this->group_service()->build_custom_data_field($org_identifier);
            $group_member_result = $this->group_service()->create_group_member($person_uuid, $group_uuid, $role_slug, $custom_data_field);
            if (is_wp_error($group_member_result)) {
                return $group_member_result;
            }

            $group_details = function_exists('wicket_get_group') ? wicket_get_group($group_uuid) : null;
            $group_name = $group_details['data']['attributes']['name'] ?? 'Unknown Group';

            $notification_result = $this->notification_service()->email_to_person_on_group_assignment($person_uuid, [
                'person_email'      => $member_data['email'],
                'notification_type' => 'group_assignment',
                'org_id'            => $org_uuid ?: $org_id,
                'group_name'        => $group_name,
            ]);

            if (is_wp_error($notification_result)) {
                $logger->error('[OrgRoster] Groups strategy email notification failed', array_merge($log_context, [
                    'error' => $notification_result->get_error_message(),
                ]));
            } else {
                $logger->info('[OrgRoster] Groups strategy email notification sent', array_merge($log_context, [
                    'group_name' => $group_name,
                ]));
            }

            $logger->info('[OrgRoster] Groups strategy member addition complete', $log_context);

            return [
                'status' => 'success',
                'message' => 'Member added to group successfully.',
                'person_uuid' => $person_uuid,
            ];

        } catch (\Exception $e) {
            $logger->error('[OrgRoster] Groups strategy add_member exception', array_merge($log_context, [
                'exception' => $e->getMessage(),
            ]));

            return new \WP_Error('add_group_member_exception', $e->getMessage());
        }
    }

    public function remove_member($org_id, $person_uuid, $context = [])
    {
        $logger = $this->get_logger();
        $log_context = [
            'source' => 'wicket-orgroster',
            'strategy' => 'groups',
            'org_id' => $org_id,
            'person_uuid' => $person_uuid,
            'group_uuid' => $context['group_uuid'] ?? null,
        ];

        try {
            $logger->info('[OrgRoster] Groups strategy remove_member invoked', $log_context);

            if (empty($context['group_uuid'])) {
                $logger->error('[OrgRoster] Groups strategy remove_member missing group_uuid', $log_context);

                return new \WP_Error('missing_group_uuid', 'Group UUID is required for this operation.');
            }

            $group_uuid = $context['group_uuid'];
            $role_slug = sanitize_key((string) ($context['role'] ?? $context['roster_role'] ?? ''));

            $current_person = wp_get_current_user();
            $manager_uuid = $current_person ? (string) $current_person->user_login : '';
            $manager_access = $this->group_service()->can_manage_group($group_uuid, $manager_uuid);
            if (empty($manager_access['allowed'])) {
                $logger->warning('[OrgRoster] Groups strategy remove_member access denied', array_merge($log_context, [
                    'manager_uuid' => $manager_uuid,
                ]));

                return new \WP_Error('no_group_access', 'You do not have permission to manage this group.');
            }

            $org_identifier = (string) ($manager_access['org_identifier'] ?? '');
            $org_uuid = (string) ($manager_access['org_uuid'] ?? $org_id);

            if (!empty($org_uuid)) {
                $org_owner = $this->organization_service()->get_organization_owner($org_uuid);
                if (!is_wp_error($org_owner) && $org_owner && $org_owner->uuid === $person_uuid) {
                    $logger->warning('[OrgRoster] Groups strategy attempted to remove organization owner', $log_context);

                    return new \WP_Error('owner_removal_forbidden', 'The organization owner (Primary Member) cannot be removed.');
                }
            }

            $orgman_config = \OrgManagement\Config\get_config();
            $groups_config = is_array($orgman_config['groups'] ?? null) ? $orgman_config['groups'] : [];
            $manage_roles = is_array($groups_config['manage_roles'] ?? null) ? $groups_config['manage_roles'] : [];
            if ($role_slug && in_array($role_slug, $manage_roles, true)) {
                $logger->warning('[OrgRoster] Groups strategy attempted to remove managing role', array_merge($log_context, [
                    'role' => $role_slug,
                ]));

                return new \WP_Error('role_removal_forbidden', 'Managing roles cannot be removed.');
            }

            $group_member_id = (string) ($context['group_member_id'] ?? '');
            if ('' === $group_member_id) {
                $group_member_id = $this->group_service()->find_group_member_id($group_uuid, $person_uuid, $org_identifier, [], $org_uuid);
            }
            if ('' === $group_member_id) {
                $logger->error('[OrgRoster] Groups strategy could not locate group member', $log_context);

                return new \WP_Error('group_member_not_found', 'Could not find the person in the specified group.');
            }

            $remove_result = $this->group_service()->remove_group_member($group_member_id);
            if (is_wp_error($remove_result)) {
                $logger->error('[OrgRoster] Groups strategy failed to remove group member', array_merge($log_context, [
                    'error' => $remove_result->get_error_message(),
                ]));

                return $remove_result;
            }

            $logger->info('[OrgRoster] Groups strategy remove_member complete', $log_context);

            return ['status' => 'success', 'message' => 'Group member removed successfully.'];

        } catch (\Exception $e) {
            $logger->error('[OrgRoster] Groups strategy remove_member exception', array_merge($log_context, [
                'exception' => $e->getMessage(),
            ]));

            return new \WP_Error('remove_group_member_exception', $e->getMessage());
        }
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
     * Lazily instantiate GroupService.
     *
     * @return GroupService
     */
    private function group_service(): GroupService
    {
        if (!isset($this->groupService)) {
            $this->groupService = new GroupService();
        }

        return $this->groupService;
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
