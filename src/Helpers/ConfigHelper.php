<?php

/**
 * Config Helper for Org Management.
 */

namespace OrgManagement\Helpers;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Config Helper class extending the base Helper.
 */
class ConfigHelper extends Helper
{
    /**
     * Check if transients cache is enabled.
     *
     * @return bool True if cache is enabled, false otherwise.
     */
    public static function is_cache_enabled(): bool
    {
        return parent::is_cache_enabled();
    }

    /**
     * Get cache duration from config.
     *
     * @return int Cache duration in seconds
     */
    public static function get_cache_duration(): int
    {
        $config = self::get_config();

        return $config['cache']['duration'] ?? (5 * MINUTE_IN_SECONDS);
    }

    /**
     * Get roles configuration.
     *
     * @return array Roles configuration
     */
    public static function get_roles_config(): array
    {
        $config = self::get_config();

        return $config['roles'] ?? [];
    }

    /**
     * Get relationships configuration.
     *
     * @return array Relationships configuration
     */
    public static function get_relationships_config(): array
    {
        $config = self::get_config();

        return $config['relationships'] ?? [];
    }

    /**
     * Get permissions configuration.
     *
     * @return array Permissions configuration
     */
    public static function get_permissions_config(): array
    {
        $config = self::get_config();

        return $config['permissions'] ?? [
            'edit_organization' => ['org_editor'],
            'manage_members' => ['membership_manager', 'membership_owner'],
            'purchase_seats' => ['membership_owner', 'membership_manager', 'org_editor'],
            'any_management' => ['org_editor', 'membership_manager', 'membership_owner'],
        ];
    }

    /**
     * Normalize role name for comparison (lowercase, replace spaces with underscores).
     *
     * @param string $role The role name to normalize
     * @return string Normalized role name
     */
    private static function normalize_role_name(string $role): string
    {
        return strtolower(str_replace(' ', '_', trim($role)));
    }

    /**
     * Normalize an array of role names.
     *
     * @param array $roles Array of role names to normalize
     * @return array Normalized role names
     */
    private static function normalize_roles(array $roles): array
    {
        return array_map([self::class, 'normalize_role_name'], $roles);
    }

    /**
     * Get roles for edit organization permission.
     *
     * @return array Array of normalized role names that can edit organization
     */
    public static function get_edit_organization_roles(): array
    {
        $config = self::get_permissions_config();
        $roles = $config['edit_organization'] ?? ['org_editor'];

        return self::normalize_roles($roles);
    }

    /**
     * Get roles for manage members permission.
     *
     * @return array Array of normalized role names that can manage members
     */
    public static function get_manage_members_roles(): array
    {
        $config = self::get_permissions_config();
        $roles = $config['manage_members'] ?? ['membership_manager', 'membership_owner'];

        return self::normalize_roles($roles);
    }

    /**
     * Get roles for purchase seats permission.
     *
     * @return array Array of normalized role names that can purchase seats
     */
    public static function get_purchase_seats_roles(): array
    {
        $config = self::get_permissions_config();
        $roles = $config['purchase_seats'] ?? ['membership_owner', 'membership_manager', 'org_editor'];

        return self::normalize_roles($roles);
    }

    /**
     * Get roles for any management permission.
     *
     * @return array Array of normalized role names that have any management access
     */
    public static function get_any_management_roles(): array
    {
        $config = self::get_permissions_config();
        $roles = $config['any_management'] ?? ['org_editor', 'membership_manager', 'membership_owner'];

        return self::normalize_roles($roles);
    }
}
