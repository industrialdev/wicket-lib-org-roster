<?php

/**
 * Direct Assignment Strategy for Roster Management.
 */

namespace OrgManagement\Services\Strategies;

use OrgManagement\Services\ConfigService;
use OrgManagement\Services\ConnectionService;
use OrgManagement\Services\MembershipService;
use OrgManagement\Services\OrganizationService;
use OrgManagement\Services\PermissionService;
use OrgManagement\Services\PersonService;
use WP_Error;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Implements the direct assignment mode for roster management.
 */
class DirectAssignmentStrategy implements RosterManagementStrategy
{
    /**
     * @var MembershipService|null
     */
    private $membershipService = null;

    /**
     * @var PersonService|null
     */
    private $personService = null;

    /**
     * @var ConnectionService|null
     */
    private $connectionService = null;

    /**
     * @var PermissionService|null
     */
    private $permissionService = null;

    /**
     * @var OrganizationService|null
     */
    private $organizationService = null;

    /**
     * @var ConfigService|null
     */
    private $configService = null;

    /**
     * @var \WC_Logger|null
     */
    private $logger = null;

    public function add_member($org_id, $member_data, $context = [])
    {
        $logger = $this->get_logger();
        $log_context = [
            'source' => 'wicket-orgman',
            'strategy' => 'direct',
            'org_id' => $org_id,
            'member_email' => $member_data['email'] ?? null,
        ];

        try {
            $logger->info('[OrgMan] Direct strategy add_member invoked', $log_context);

            $person_uuid = $this->person_service()->createOrUpdatePerson($member_data);
            if (is_wp_error($person_uuid)) {
                $logger->error('[OrgMan] Failed to create/update person for member addition', array_merge($log_context, [
                    'error' => $person_uuid->get_error_message(),
                ]));

                return $person_uuid;
            }
            $log_context['person_uuid'] = $person_uuid;
            $logger->debug('[OrgMan] Person record ready for membership assignment', $log_context);

            // Get configuration for member addition settings
            $config = \OrgManagement\Config\OrgManConfig::get();
            $base_member_role = $config['member_addition']['base_member_role'] ?? 'member';
            $auto_assign_roles = $config['member_addition']['auto_assign_roles'] ?? [];

            // Use relationship type from context if provided, otherwise use config default
            $relationship_type = !empty($context['relationship_type'])
                ? $context['relationship_type']
                : ($config['relationships']['member_addition_type'] ?? 'position');
            $relationship_description = $context['relationship_description'] ?? $member_data['relationship_description'] ?? '';
            $relationship_description = is_string($relationship_description) ? sanitize_textarea_field($relationship_description) : '';

            // Map custom relationship types to Wicket API types if needed
            $custom_types = $config['relationship_types']['custom_types'] ?? [];
            if (isset($custom_types[$relationship_type])) {
                // This is a custom relationship type - we'll use it as-is
                // Future enhancement: map to actual Wicket relationship types if needed
            }

            $membership_uuid = $this->resolve_membership_uuid($org_id, $context);
            if (is_wp_error($membership_uuid)) {
                $logger->error('[OrgMan] Unable to resolve membership UUID for organization', array_merge($log_context, [
                    'error' => $membership_uuid->get_error_message(),
                ]));

                return $membership_uuid;
            }
            $log_context['membership_uuid'] = $membership_uuid;

            $has_membership = $this->connectionService()->personHasMembership($person_uuid, $membership_uuid);
            if (is_wp_error($has_membership)) {
                return $has_membership;
            }

            if (!$has_membership) {
                $connection_payload = $this->connectionService()->buildConnectionPayload(
                    $person_uuid,
                    $org_id,
                    'person_to_organization',
                    $relationship_type,
                    $relationship_description
                );

                $response_connection = $this->connectionService()->createConnection($connection_payload);

                if (isset($response_connection['error']) && $response_connection['error'] === true) {
                    return new WP_Error('connection_creation_failed', $response_connection['message'] ?? 'Failed to add employee connection');
                }
            }

            // Assign person to membership seat to give them active membership

            $membership_assignment_result = $this->assign_person_to_membership_seat($person_uuid, $membership_uuid);
            if (is_wp_error($membership_assignment_result)) {
                $logger->error('[OrgMan] Membership assignment failed', array_merge($log_context, [
                    'error' => $membership_assignment_result->get_error_message(),
                ]));

                return $membership_assignment_result;
            }
            $logger->info('[OrgMan] Assigned person to membership seat', $log_context);

            // Assign base member role from config
            $role_result = $this->assign_role($person_uuid, $base_member_role, $org_id);
            if (is_wp_error($role_result)) {
                $logger->error('[OrgMan] Base member role assignment failed', array_merge($log_context, [
                    'role' => $base_member_role,
                    'error' => $role_result->get_error_message(),
                ]));

                return $role_result;
            }
            $logger->debug('[OrgMan] Base member role assigned', array_merge($log_context, [
                'role' => $base_member_role,
            ]));

            // Assign site-specific auto-roles from config
            if (!empty($auto_assign_roles)) {
                $logger->debug('[OrgMan] Assigning configured auto roles', array_merge($log_context, [
                    'auto_roles' => $auto_assign_roles,
                ]));

                $auto_roles_result = $this->assign_additional_roles($person_uuid, $org_id, $auto_assign_roles);
                if (is_wp_error($auto_roles_result)) {
                    $logger->error('[OrgMan] Auto-role assignment failed', array_merge($log_context, [
                        'roles' => $auto_assign_roles,
                        'error' => $auto_roles_result->get_error_message(),
                    ]));

                    return $auto_roles_result;
                }
            }

            // Assign any additional roles from the form
            $additional_roles = $context['roles'] ?? $member_data['roles'] ?? [];
            $additional_result = $this->assign_additional_roles($person_uuid, $org_id, $additional_roles);
            if (is_wp_error($additional_result)) {
                $logger->error('[OrgMan] Additional role assignment failed', array_merge($log_context, [
                    'roles' => $additional_roles,
                    'error' => $additional_result->get_error_message(),
                ]));

                return $additional_result;
            }
            if (!empty($additional_roles)) {
                $logger->debug('[OrgMan] Additional roles assigned', array_merge($log_context, [
                    'roles' => $additional_roles,
                ]));
            }

            $this->log_touchpoint($person_uuid, $org_id, $member_data, $context);
            $logger->debug('[OrgMan] Touchpoint logged for member addition', $log_context);

            $email_result = $this->send_assignment_email($person_uuid, $org_id, [
                'fallback_email' => $member_data['email'] ?? null,
            ]);
            if (is_wp_error($email_result)) {
                // Do not block success on email failures; log for observability.
                $logger->error('[OrgMan] Failed to send assignment email', array_merge($log_context, [
                    'error' => $email_result->get_error_message(),
                ]));
            } else {
                $logger->info('[OrgMan] Assignment email dispatched', $log_context);
            }

            $logger->info('[OrgMan] Member added successfully via direct strategy', $log_context);

            return [
                'status'      => 'success',
                'message'     => 'Member added successfully.',
                'person_uuid' => $person_uuid,
            ];

        } catch (\Exception $e) {
            $logger->error('[OrgMan] Direct strategy add_member threw exception', array_merge($log_context, [
                'exception' => $e->getMessage(),
            ]));

            return new WP_Error('add_member_exception', $e->getMessage());
        }
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
     * Resolve the membership UUID for an organization.
     *
     * @param string $org_id
     * @param array  $context
     * @return string|WP_Error
     */
    private function resolve_membership_uuid($org_id, array $context = [])
    {
        $org_id = sanitize_text_field((string) $org_id);
        if ('' === $org_id) {
            return new WP_Error('invalid_org_id', 'Organization identifier is required.');
        }

        $context_membership_uuid = sanitize_text_field((string) ($context['membership_uuid'] ?? $context['membership_id'] ?? ''));
        if ('' !== $context_membership_uuid) {
            $membership_data = $this->membership_service()->getOrgMembershipData($context_membership_uuid);
            if (empty($membership_data) || !is_array($membership_data)) {
                return new WP_Error('invalid_membership_uuid', 'Membership UUID is invalid or unavailable.');
            }

            $membership_org_id = $membership_data['data']['relationships']['organization']['data']['id'] ?? '';
            if ('' !== $membership_org_id && $membership_org_id !== $org_id) {
                return new WP_Error('membership_org_mismatch', 'Membership does not belong to the selected organization.');
            }

            return $context_membership_uuid;
        }

        $membership_uuid = $this->membership_service()->getOrganizationMembershipUuid($org_id);

        if (empty($membership_uuid)) {
            return new WP_Error('no_membership', 'Could not find a valid corporate membership for this organization.');
        }

        return $membership_uuid;
    }

    /**
     * Assign additional roles, if any.
     *
     * @param string       $person_uuid
     * @param string       $org_id
     * @param array|string $roles
     * @return true|WP_Error
     */
    private function assign_additional_roles(string $person_uuid, string $org_id, $roles)
    {
        $roles = $this->normalize_roles($roles);

        // Filter out membership_owner if configured to prevent assignment
        $config = \OrgManagement\Config\OrgManConfig::get();
        if (!empty($config['permissions']['prevent_owner_assignment'])) {
            $roles = array_values(array_diff($roles, ['membership_owner']));
        }

        if (empty($roles)) {
            return true;
        }

        foreach ($roles as $role) {
            $result = $this->assign_role($person_uuid, $role, $org_id);
            if (is_wp_error($result)) {
                $this->get_logger()->error('[OrgMan] Additional role assignment failed', [
                    'source' => 'wicket-orgman',
                    'strategy' => 'direct',
                    'person_uuid' => $person_uuid,
                    'org_id' => $org_id,
                    'role' => $role,
                    'error' => $result->get_error_message(),
                ]);

                return $result;
            }
        }

        return true;
    }

    /**
     * Normalize role input to an array of unique role slugs.
     *
     * @param array|string $roles
     * @return array
     */
    private function normalize_roles($roles): array
    {
        if (is_string($roles)) {
            $roles = array_map('trim', explode(',', $roles));
        }

        if (!is_array($roles)) {
            return [];
        }

        $sanitized = [];
        foreach ($roles as $role) {
            $role = sanitize_key((string) $role);
            if ('' !== $role && 'member' !== $role) {
                $sanitized[] = $role;
            }
        }

        return array_values(array_unique($sanitized));
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
        $logger = $this->get_logger();
        $context = [
            'source' => 'wicket-orgman',
            'strategy' => 'direct',
            'person_uuid' => $person_uuid,
            'membership_uuid' => $membership_uuid,
        ];

        if (!function_exists('wicket_assign_person_to_org_membership')) {
            $logger->error('[OrgMan] Membership assignment helper missing', $context);

            return new WP_Error('missing_dependency', 'Membership assignment helper is unavailable.');
        }

        try {
            // Get organization membership data to pass to the assignment function
            $membership_data = $this->membership_service()->getOrgMembershipData($membership_uuid);
            if (is_wp_error($membership_data)) {
                $logger->error('[OrgMan] Membership data lookup returned WP_Error', array_merge($context, [
                    'error' => $membership_data->get_error_message(),
                ]));

                return $membership_data;
            }

            if (empty($membership_data) || empty($membership_data['data'])) {
                $logger->error('[OrgMan] Membership data missing payload', $context);

                return new WP_Error('membership_data_missing', 'Membership details unavailable.');
            }

            // Extract membership type ID from relationships
            $membership_type_id = $membership_data['data']['relationships']['membership']['data']['id'] ?? '';

            // Fallback: inspect included resources for memberships/membership_types entries
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
                $logger->error('[OrgMan] Membership type ID missing in membership data', $context);

                return new WP_Error('membership_type_missing', 'Could not find membership type ID.');
            }

            // Pass membership data as array; the helper expects array access
            $result = wicket_assign_person_to_org_membership(
                $person_uuid,        // person ID
                $membership_type_id, // membership type ID
                $membership_uuid,    // organization membership ID
                $membership_data     // organization membership data
            );

            // Check if the API call was successful
            if (empty($result) || isset($result['errors'])) {
                $error_message = $result['errors'][0]['detail'] ?? 'Failed to assign person to membership seat.';
                $logger->warning('[OrgMan] Membership assignment API returned error, verifying existing membership', array_merge($context, [
                    'membership_type_id' => $membership_type_id,
                    'api_error' => $error_message,
                ]));

                $post_check = $this->connectionService()->personHasMembership($person_uuid, $membership_uuid);
                if (true === $post_check) {
                    $logger->info('[OrgMan] Membership assignment already present after API error', $context);

                    return true;
                }

                if (is_wp_error($post_check)) {
                    $logger->error('[OrgMan] Membership verification after API error failed', array_merge($context, [
                        'verification_error' => $post_check->get_error_message(),
                    ]));
                }

                return new WP_Error('membership_assignment_failed', $error_message);
            }

            $logger->info('[OrgMan] Membership assignment API succeeded', array_merge($context, [
                'membership_type_id' => $membership_type_id,
            ]));

            return true;

        } catch (\Throwable $e) {
            $logger->error('[OrgMan] Membership assignment threw exception', array_merge($context, [
                'exception' => $e->getMessage(),
            ]));

            return new WP_Error('membership_assignment_exception', $e->getMessage());
        }
    }

    /**
     * Assign a single role through Wicket.
     *
     * @param string $person_uuid
     * @param string $role
     * @param string $org_id
     * @return true|WP_Error
     */
    private function assign_role(string $person_uuid, string $role, string $org_id)
    {
        if (!function_exists('wicket_assign_role')) {
            return new WP_Error('missing_dependency', 'Role assignment helper is unavailable.');
        }

        try {
            $result = wicket_assign_role($person_uuid, $role, $org_id);
        } catch (\Throwable $e) {
            return new WP_Error('role_assignment_failed', $e->getMessage());
        }

        if (false === $result) {
            return new WP_Error('role_assignment_failed', sprintf('Failed assigning role %s.', $role));
        }

        return true;
    }

    /**
     * Lazily instantiate PermissionService.
     *
     * @return PermissionService|null
     */
    private function permission_service(): ?PermissionService
    {
        if (isset($this->permissionService)) {
            return $this->permissionService;
        }

        if (class_exists(PermissionService::class)) {
            $this->permissionService = new PermissionService();
        }

        return $this->permissionService;
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
     * Log a touchpoint to track member addition.
     *
     * @param string $person_uuid
     * @param string $org_id
     * @param array  $member_data
     * @param array  $context
     * @return void
     */
    private function log_touchpoint(string $person_uuid, string $org_id, array $member_data, array $context): void
    {
        if (!function_exists('write_touchpoint') || !function_exists('get_create_touchpoint_service_id')) {
            return;
        }

        $org_name = sanitize_text_field($context['org_name'] ?? '');
        $details = sprintf(
            "Person was added to organization %s on %s.\n\nPerson: %s %s\n\nEmail: %s\n\nID: %s",
            $org_name,
            gmdate('c'),
            sanitize_text_field($member_data['first_name'] ?? ''),
            sanitize_text_field($member_data['last_name'] ?? ''),
            sanitize_email($member_data['email'] ?? ''),
            $person_uuid
        );

        $touchpoint_params = [
            'person_id' => $person_uuid,
            'action'    => 'Organization member added',
            'details'   => $details,
            'data'      => ['org_id' => $org_id],
        ];

        try {
            $service_id = get_create_touchpoint_service_id('Roster Manage', 'Added member');
            write_touchpoint($touchpoint_params, $service_id);
        } catch (\Throwable $e) {
            error_log('[OrgMan] Failed to write touchpoint: ' . $e->getMessage());
        }
    }

    /**
     * Log a touchpoint to track member removal.
     *
     * @param string $person_uuid
     * @param string $org_id
     * @param array  $context
     * @return void
     */
    private function log_removal_touchpoint(string $person_uuid, string $org_id, array $context): void
    {
        if (!function_exists('write_touchpoint') || !function_exists('get_create_touchpoint_service_id')) {
            return;
        }

        // Fetch person data
        if (function_exists('wicket_get_person_by_id')) {
            $person = wicket_get_person_by_id($person_uuid);
            if ($person) {
                $first_name = sanitize_text_field($person->given_name ?? '');
                $last_name = sanitize_text_field($person->family_name ?? '');
                $email = sanitize_email($person->primary_email_address ?? '');
            } else {
                $first_name = $last_name = $email = '';
            }
        } else {
            $first_name = $last_name = $email = '';
        }

        $org_name = sanitize_text_field($context['org_name'] ?? '');
        $details = sprintf(
            "Person was removed from organization %s on %s.\n\nPerson: %s %s\n\nEmail: %s\n\nID: %s",
            $org_name,
            gmdate('c'),
            $first_name,
            $last_name,
            $email,
            $person_uuid
        );

        $touchpoint_params = [
            'person_id' => $person_uuid,
            'action'    => 'Organization member removed',
            'details'   => $details,
            'data'      => ['org_id' => $org_id],
        ];

        try {
            $service_id = get_create_touchpoint_service_id('Roster Manage', 'Removed member');
            write_touchpoint($touchpoint_params, $service_id);
        } catch (\Throwable $e) {
            error_log('[OrgMan] Failed to write removal touchpoint: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch assignment email notification.
     *
     * @param string $person_uuid
     * @param string $org_id
     * @param array  $options {
     *     @type string|null $fallback_email Optional email address captured from the form.
     * }
     * @return true|WP_Error
     */
    private function send_assignment_email(string $person_uuid, string $org_id, array $options = [])
    {
        $logger = $this->get_logger();
        $context = [
            'source' => 'wicket-orgman',
            'strategy' => 'direct',
            'org_id' => $org_id,
            'person_uuid' => $person_uuid,
        ];
        $fallback_email = isset($options['fallback_email']) ? sanitize_email((string) $options['fallback_email']) : '';

        if (!function_exists('wicket_get_organization') || !function_exists('wicket_get_person_by_id')) {
            $logger->error('[OrgMan] Email notification dependencies missing', $context);

            return new WP_Error('missing_dependency', 'Email notification dependencies are unavailable.');
        }

        $logger->debug('[OrgMan] Preparing assignment email payload', $context);
        $org = wicket_get_organization($org_id);
        $person = wicket_get_person_by_id($person_uuid);
        $home_url = home_url();
        $site_url = get_site_url();
        $site_name = get_bloginfo('name');
        $base_domain = wp_parse_url($site_url, PHP_URL_HOST);

        if ($base_domain === 'localhost') {
            $base_domain = 'localhost.com';
        }

        $to = $person->primary_email_address ?? '';

        if (empty($to) && !empty($fallback_email)) {
            $to = $fallback_email;
            $logger->warning('[OrgMan] Assignment email falling back to provided email', array_merge($context, [
                'fallback_email' => $fallback_email,
            ]));
        }

        if (empty($to)) {
            $logger->error('[OrgMan] Assignment email aborted: missing person email', $context);

            return new WP_Error('person_email_missing', 'Unable to determine person email address.');
        }

        $lang = wicket_get_current_language();
        $organization_name = $site_name;

        if ($org && isset($org['data']['attributes'])) {
            $attributes = $org['data']['attributes'];
            $name_key = 'legal_name_' . $lang;
            if (isset($attributes[$name_key]) && '' !== $attributes[$name_key]) {
                $organization_name = $attributes[$name_key];
            }
        }

        $to = sanitize_email($person->primary_email_address);
        $first_name = sanitize_text_field($person->given_name ?? '');
        $subject = sprintf('Welcome to %s', $organization_name);

        // Get configuration for email
        $config = \OrgManagement\Config\OrgManConfig::get();
        $confirmation_email_from = $config['notifications']['confirmation_email_from'] ?? 'no-reply@wicketcloud.com';

        $body = sprintf(
            "Hi %s,<br>
            <p>You have been assigned a membership as part of %s.</p>
            <p>You will receive an account confirmation email from %s, this will allow you to set your password and login for the first time.</p>
            <p>Going forward you can visit <a href='%s'>%s</a> and login to complete your profile and access your resources.</p>
            <br>
            Thank you,<br>
            %s",
            esc_html($first_name),
            esc_html($organization_name),
            esc_html($confirmation_email_from),
            esc_url($home_url),
            esc_html($site_name),
            esc_html($organization_name)
        );

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        if ($base_domain) {
            $headers[] = sprintf('From: %s <no-reply@%s>', $organization_name, $base_domain);
        }

        $logger->debug('[OrgMan] Dispatching assignment email', array_merge($context, [
            'recipient' => $to,
            'subject' => $subject,
        ]));

        $sent = wp_mail($to, $subject, $body, $headers);

        if (!$sent) {
            $logger->error('[OrgMan] Assignment email send failed', array_merge($context, [
                'recipient' => $to,
            ]));

            return new WP_Error('email_failed', 'Failed to send assignment email.');
        }

        $logger->info('[OrgMan] Assignment email sent', array_merge($context, [
            'recipient' => $to,
        ]));

        return true;
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

    public function remove_member($org_id, $person_uuid, $context = [])
    {
        try {
            $required_functions = [];
            foreach ($required_functions as $func) {
                if (!function_exists($func)) {
                    return new WP_Error('missing_function', "Legacy function {$func} not found.");
                }
            }

            // Check if membership_owner removal is prevented by configuration
            $config = \OrgManagement\Config\OrgManConfig::get();
            $prevent_owner_removal = $config['permissions']['prevent_owner_removal'] ?? false;

            if ($prevent_owner_removal) {
                $org_owner = $this->organization_service()->get_organization_owner($org_id);
                if (!is_wp_error($org_owner) && $org_owner && $org_owner->uuid === $person_uuid) {
                    return new WP_Error('owner_removal_forbidden', 'The organization owner (Primary Member) cannot be removed.');
                }
            }

            // Get person membership ID from context
            $person_membership_id = $context['person_membership_id'] ?? null;

            if (empty($person_membership_id)) {
                return new WP_Error('missing_person_membership_id', 'Person membership ID is required to remove a member.');
            }

            // For direct strategy (non-cascading), do not end membership to keep relationship active
            // Only remove org-scoped roles

            // Remove all org-scoped roles
            $roles_to_remove = $this->permission_service()->get_person_current_roles_by_org_id($person_uuid, $org_id);
            if (!empty($roles_to_remove)) {
                foreach ($roles_to_remove as $role) {
                    wicket_remove_role($person_uuid, $role, $org_id);
                }
            }

            $this->log_removal_touchpoint($person_uuid, $org_id, $context);

            return ['status' => 'success', 'message' => 'Member removed successfully.'];

        } catch (\Exception $e) {
            return new WP_Error('remove_member_exception', $e->getMessage());
        }
    }
}
