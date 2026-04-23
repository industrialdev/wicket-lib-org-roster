<?php

declare(strict_types=1);

namespace OrgManagement\Services;

use OrgManagement\Helpers\ConfigHelper;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Standardized caching service for the library with version salt.
 */
class CacheService
{
    /**
     * Get cached data by key.
     *
     * @param string $key The cache key.
     * @return mixed|false Cached data or false if not found/disabled.
     */
    public function get(string $key)
    {
        if (!ConfigHelper::is_cache_enabled()) {
            return false;
        }

        $versionedKey = $this->getVersionedKey($key);
        return get_transient($versionedKey);
    }

    /**
     * Set data in cache.
     *
     * @param string   $key      The cache key.
     * @param mixed    $data     The data to cache.
     * @param int|null $duration Optional duration in seconds.
     * @return bool True if set, false otherwise.
     */
    public function set(string $key, $data, ?int $duration = null): bool
    {
        if (!ConfigHelper::is_cache_enabled()) {
            return false;
        }

        $cacheDuration = $duration ?? ConfigHelper::get_cache_duration();
        $versionedKey = $this->getVersionedKey($key);
        
        return set_transient($versionedKey, $data, $cacheDuration);
    }

    /**
     * Delete data from cache.
     *
     * @param string $key The cache key.
     * @return bool True if successful, false otherwise.
     */
    public function delete(string $key): bool
    {
        if (!ConfigHelper::is_cache_enabled()) {
            return false;
        }

        $versionedKey = $this->getVersionedKey($key);
        return delete_transient($versionedKey);
    }

    /**
     * Prepend version salt to cache key.
     *
     * @param string $key
     * @return string
     */
    private function getVersionedKey(string $key): string
    {
        $salt = ConfigHelper::get_cache_salt();
        return 'orgman_' . $salt . '_' . md5($key);
    }

    /**
     * Standardized method to clear member-related caches for an organization.
     * Clears both versioned and legacy "ghost" caches.
     */
    public function invalidateMemberCache(string $membershipUuid, ?string $orgUuid = null, ?string $personUuid = null): void
    {
        if (empty($membershipUuid)) {
            return;
        }

        $logger = \Wicket()->log();
        $logger->debug('[OrgMan] CacheService: Invalidating member cache', [
            'membership_uuid' => $membershipUuid,
            'org_uuid'        => $orgUuid,
            'person_uuid'     => $personUuid,
        ]);

        if ($personUuid && $orgUuid) {
            $this->delete('orgman_person_roles_' . md5($personUuid . $orgUuid));
            // Legacy key format
            delete_transient('orgman_person_roles_' . md5($personUuid . $orgUuid));
        }

        $this->delete('orgman_membership_data_' . md5($membershipUuid));
        delete_transient('orgman_membership_data_' . md5($membershipUuid));

        // Invalidate common page/size combinations (first 5 pages)
        $commonPageSizes = [10, 15, 20, 25, 50, 100];
        for ($p = 1; $p <= 5; $p++) {
            foreach ($commonPageSizes as $size) {
                $this->delete('orgman_members_' . md5($membershipUuid . $p . $size));
                
                // Legacy "initial" key formats used in wicket-wp-stack
                delete_transient('orgman_members_initial_' . md5($membershipUuid . '_' . $p . '_' . $size));
            }
        }
    }
}
