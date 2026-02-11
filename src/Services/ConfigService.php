<?php

/**
 * Config Service for handling configuration.
 */

namespace OrgManagement\Services;

// Exit if accessed directly.
if (!defined('ABSPATH') && !defined('WICKET_ORGROSTER_DOINGTESTS')) {
    exit;
}

/**
 * Handles configuration for the application.
 */
class ConfigService
{
    /**
     * Get the current roster management mode.
     *
     * @return string The current roster mode.
     */
    public function get_roster_mode()
    {
        $config = \OrgManagement\Config\get_config();
        $default_strategy = $config['roster']['strategy'] ?? 'cascade';

        return $default_strategy;
    }

    /**
     * Check if additional seats functionality is enabled.
     *
     * @return bool True if additional seats functionality is enabled.
     */
    public function is_additional_seats_enabled()
    {
        $config = \OrgManagement\Config\get_config();
        $default_enabled = $config['additional_seats']['enabled'] ?? true;

        return apply_filters('wicket/acc/orgman/additional_seats_enabled', $default_enabled);
    }

    /**
     * Get the SKU for additional seats product.
     *
     * @return string The SKU for the additional seats product.
     */
    public function get_additional_seats_sku()
    {
        $config = \OrgManagement\Config\get_config();
        $default_sku = $config['additional_seats']['sku'] ?? 'additional-seats';

        return apply_filters('wicket/acc/orgman/additional_seats_sku', $default_sku);
    }

    /**
     * Get the Gravity Form ID for additional seats purchase.
     *
     * @return int The Gravity Form ID.
     */
    public function get_additional_seats_form_id()
    {
        $config = \OrgManagement\Config\get_config();
        $default_form_id = $config['additional_seats']['form_id'] ?? 0;

        if ((int) $default_form_id === 0 && function_exists('wicket_gf_get_form_id_by_slug')) {
            $slug = $config['additional_seats']['form_slug'] ?? 'additional-seats';
            $slug = is_string($slug) ? trim($slug) : '';
            if ($slug !== '') {
                $detected_form_id = wicket_gf_get_form_id_by_slug($slug);
                $default_form_id = $detected_form_id ? (int) $detected_form_id : 0;
            }
        }

        return apply_filters('wicket/acc/orgman/additional_seats_form_id', (int) $default_form_id);
    }

    /**
     * Get a localized Gravity Form ID for the current language.
     *
     * @param int $form_id The base Gravity Form ID.
     * @return int The localized form ID.
     */
    public function get_localized_form_id(int $form_id): int
    {
        $form_id = absint($form_id);
        if ($form_id === 0) {
            return 0;
        }

        if (function_exists('wicket_is_multilang_active') && wicket_is_multilang_active()) {
            $current_lang = function_exists('wicket_get_current_language') ? wicket_get_current_language() : null;
            if ($current_lang && function_exists('apply_filters')) {
                $element_type = defined('ICL_GRAVITY_FORM_ELEMENT_TYPE') ? ICL_GRAVITY_FORM_ELEMENT_TYPE : 'gravity_form';
                $translated_id = apply_filters('wpml_object_id', $form_id, $element_type, false, $current_lang);
                if (empty($translated_id) && $element_type !== 'gf_form') {
                    $translated_id = apply_filters('wpml_object_id', $form_id, 'gf_form', false, $current_lang);
                }
                if (!empty($translated_id)) {
                    $form_id = (int) $translated_id;
                }
            }
        }

        return (int) $form_id;
    }

    /**
     * Get the Gravity Form ID for additional seats, localized to current language.
     *
     * @return int The localized Gravity Form ID.
     */
    public function get_additional_seats_form_id_for_current_language(): int
    {
        return $this->get_localized_form_id($this->get_additional_seats_form_id());
    }

    /**
     * Get additional seats minimum quantity.
     *
     * @return int The minimum quantity.
     */
    public function get_additional_seats_min_quantity()
    {
        $config = \OrgManagement\Config\get_config();
        $default_min_quantity = $config['additional_seats']['min_quantity'] ?? 1;

        return apply_filters('wicket/acc/orgman/additional_seats_min_quantity', $default_min_quantity);
    }

    /**
     * Get additional seats maximum quantity.
     *
     * @return int The maximum quantity.
     */
    public function get_additional_seats_max_quantity()
    {
        $config = \OrgManagement\Config\get_config();
        $default_max_quantity = $config['additional_seats']['max_quantity'] ?? 100;

        return apply_filters('wicket/acc/orgman/additional_seats_max_quantity', $default_max_quantity);
    }

    /**
     * Get allowed document types.
     *
     * @return array Array of allowed document file types.
     */
    public function get_allowed_document_types()
    {
        $config = \OrgManagement\Config\get_config();
        $default_types = $config['documents']['allowed_types'] ?? [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif',
        ];

        return apply_filters('wicket/acc/orgman/allowed_document_types', $default_types);
    }

    /**
     * Get maximum document size.
     *
     * @return int Maximum document size in bytes.
     */
    public function get_max_document_size()
    {
        $config = \OrgManagement\Config\get_config();
        $default_size = $config['documents']['max_size'] ?? (10 * 1024 * 1024); // 10MB default

        return apply_filters('wicket/acc/orgman/max_document_size', $default_size);
    }

    /**
     * Get business info seat limit information.
     *
     * @return string|null Custom seat limit information or null.
     */
    public function get_business_info_seat_limit_info()
    {
        $config = \OrgManagement\Config\get_config();
        $default_info = $config['business_info']['seat_limit_info'] ?? null;

        return apply_filters('wicket/acc/orgman/business_info_seat_limit', $default_info);
    }

    /**
     * Get the supplemental members page URL.
     *
     * @param string $org_uuid The organization UUID.
     * @return string The URL for the supplemental members page.
     */
    public function get_supplemental_members_url($org_uuid = '')
    {
        // Find the my-account CPT page with slug 'supplemental-members'
        $args = [
            'post_type' => 'my-account',
            'name' => 'supplemental-members',
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

            $base_url = get_permalink($post_id);
        } else {
            return '';
        }

        if (!empty($org_uuid)) {
            return add_query_arg('org_uuid', $org_uuid, $base_url);
        }

        return $base_url;
    }
}
