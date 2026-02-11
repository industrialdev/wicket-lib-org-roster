<?php

/**
 * Base Helper class for Org Management.
 */

namespace OrgManagement\Helpers;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Helper class that provides common functionality for all helper classes.
 */
abstract class Helper
{
    /**
     * Get logger instance.
     *
     * @return \WC_Logger
     */
    protected static function get_logger()
    {
        return wc_get_logger();
    }

    /**
     * Check if logging is enabled for a specific level based on environment.
     *
     * @param string $level The log level (critical, error, warning, debug)
     * @return bool
     */
    protected static function is_log_enabled(string $level): bool
    {
        $env = wp_get_environment_type();

        // Default settings based on environment
        if ('production' === $env) {
            // Only critical in production
            $allowed_levels = ['critical'];
        } else {
            // All levels in other environments (staging, development, local)
            $allowed_levels = ['critical', 'error', 'warning', 'debug', 'info', 'notice', 'alert', 'emergency'];
        }

        // Allow overriding via filter
        /**
         * Filter the allowed log levels for OrgManagement.
         *
         * @param array $allowed_levels Array of allowed log levels.
         * @param string $env Current environment type.
         */
        $allowed_levels = apply_filters('wicket_orgman_log_levels', $allowed_levels, $env);

        return in_array($level, $allowed_levels, true);
    }

    /**
     * Log critical message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function log_critical(string $message, array $context = []): void
    {
        if (!self::is_log_enabled('critical')) {
            return;
        }

        $logger = self::get_logger();
        if ($logger) {
            $logger->critical($message, array_merge(['source' => 'wicket-orgman'], $context));
        }
    }

    /**
     * Log debug message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function log_debug(string $message, array $context = []): void
    {
        if (!self::is_log_enabled('debug')) {
            return;
        }

        $logger = self::get_logger();
        if ($logger) {
            $logger->debug($message, array_merge(['source' => 'wicket-orgman'], $context));
        }
    }

    /**
     * Log error message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function log_error(string $message, array $context = []): void
    {
        if (!self::is_log_enabled('error')) {
            return;
        }

        $logger = self::get_logger();
        if ($logger) {
            $logger->error($message, array_merge(['source' => 'wicket-orgman'], $context));
        }
    }

    /**
     * Log warning message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function log_warning(string $message, array $context = []): void
    {
        if (!self::is_log_enabled('warning')) {
            return;
        }

        $logger = self::get_logger();
        if ($logger) {
            $logger->warning($message, array_merge(['source' => 'wicket-orgman'], $context));
        }
    }

    /**
     * Log info message.
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function log_info(string $message, array $context = []): void
    {
        if (!self::is_log_enabled('info')) {
            return;
        }

        $logger = self::get_logger();
        if ($logger) {
            $logger->info($message, array_merge(['source' => 'wicket-orgman'], $context));
        }
    }

    /**
     * Sanitize a text field.
     *
     * @param mixed $value The value to sanitize
     * @return string The sanitized value
     */
    protected static function sanitize_text($value): string
    {
        return sanitize_text_field((string) $value);
    }

    /**
     * Get config value.
     *
     * @return array The configuration array
     */
    protected static function get_config(): array
    {
        return \OrgManagement\Config\get_config();
    }

    /**
     * Check if cache is enabled.
     *
     * @return bool True if cache is enabled, false otherwise
     */
    protected static function is_cache_enabled(): bool
    {
        $config = self::get_config();

        return $config['cache']['enabled'] ?? true;
    }

    /**
     * Check if relationship type should be hidden from UI.
     *
     * @return bool True if relationship type should be hidden, false otherwise
     */
    public static function should_hide_relationship_type(): bool
    {
        $config = self::get_config();

        return $config['ui']['hide_relationship_type'] ?? false;
    }

    /**
     * Check if member job title should be shown on cards.
     *
     * @return bool True if job title should be shown, false otherwise
     */
    public static function should_show_member_job_title(): bool
    {
        $config = self::get_config();

        return $config['ui']['member_card_fields']['job_title']['enabled'] ?? true;
    }

    /**
     * Check if member description should be shown on cards.
     *
     * @return bool True if description should be shown, false otherwise
     */
    public static function should_show_member_description(): bool
    {
        $config = self::get_config();

        return $config['ui']['member_card_fields']['description']['enabled'] ?? true;
    }

    /**
     * Get WPML-aware permalink for a my-account CPT page by slug.
     * Automatically detects current language and returns translated permalink.
     *
     * @param string $slug The page slug (e.g., 'organization-members', 'supplemental-members')
     * @param string $fallback_path Optional fallback path if page not found (e.g., '/my-account/organization-members/')
     * @return string The permalink URL
     */
    public static function get_my_account_page_url(string $slug, string $fallback_path = ''): string
    {
        $args = [
            'post_type' => 'my-account',
            'name' => $slug,
            'numberposts' => 1,
        ];

        $posts = get_posts($args);

        if (!empty($posts)) {
            $post_id = $posts[0]->ID;

            // Only attempt multilingual translation if multilingual plugin is active
            if (function_exists('wicket_is_multilang_active') && wicket_is_multilang_active()) {
                // Get current language
                $current_lang = function_exists('wicket_get_current_language') ? wicket_get_current_language() : null;

                // Get translated post ID if language is available
                if ($current_lang && function_exists('apply_filters')) {
                    $translated_post_id = apply_filters('wpml_object_id', $post_id, 'my-account', false, $current_lang);
                    if ($translated_post_id) {
                        $post_id = $translated_post_id;
                    }
                }
            }

            return get_permalink($post_id);
        }

        // Fallback if page not found
        return !empty($fallback_path) ? home_url($fallback_path) : '';
    }
}
