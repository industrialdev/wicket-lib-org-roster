<?php

/**
 * Content template for Supplemental Members page.
 */

declare(strict_types=1);

namespace OrgManagement\Templates;

// Ensure this file is not accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get URL parameters
$org_uuid = isset($_GET['org_uuid']) ? sanitize_text_field($_GET['org_uuid']) : '';
$membership_id = isset($_GET['membership_id']) ? sanitize_text_field($_GET['membership_id']) : '';
if (empty($membership_id) && isset($_GET['membership_uuid'])) {
    $membership_id = sanitize_text_field($_GET['membership_uuid']);
}
$gf_id = isset($_GET['gf_id']) ? absint($_GET['gf_id']) : 0;

// Helper function to get my-account CPT page URL (WPML-aware)
if (!function_exists('OrgManagement\Templates\get_my_account_page_url')) {
    function get_my_account_page_url($slug)
    {
        return \OrgManagement\Helpers\Helper::get_my_account_page_url($slug, "/my-account/{$slug}/");
    }
}

// Check if user can purchase additional seats
$config_service = new \OrgManagement\Services\ConfigService();
$additional_seats_service = new \OrgManagement\Services\AdditionalSeatsService($config_service);

if (!$additional_seats_service->can_purchase_additional_seats($org_uuid)) {
    ?>
<div class="woocommerce">
    <div class="woocommerce-notices-wrapper">
        <div class="woocommerce-error">
            <?php esc_html_e('You are not authorized to purchase additional seats for this organization.', 'wicket-acc'); ?>
        </div>
    </div>
    <p>
        <a href="<?php echo esc_url(get_my_account_page_url('organization-members')); ?>"
            class="button">
            <?php esc_html_e('Back to Organization Members', 'wicket-acc'); ?>
        </a>
    </p>
</div>
<?php
    return;
}

// Get organization information
$organization_service = new \OrgManagement\Services\OrganizationService();
$organizations = $organization_service->get_user_organizations(wp_get_current_user()->user_login);
$current_organization = null;

foreach ($organizations as $org) {
    if ($org['id'] === $org_uuid) {
        $current_organization = $org;
        break;
    }
}

?>
<div class="woocommerce">
    <div class="entry-content">
        <h1><?php esc_html_e('Purchase Additional Seats', 'wicket-acc'); ?>
        </h1>

        <?php if ($current_organization): ?>
        <div class="orgman-org-info">
            <h3><?php esc_html_e('Organization Details', 'wicket-acc'); ?>
            </h3>
            <p><strong><?php esc_html_e('Organization:', 'wicket-acc'); ?></strong>
                <?php echo esc_html($current_organization['name'] ?? ''); ?>
            </p>
            <p><strong><?php esc_html_e('Membership ID:', 'wicket-acc'); ?></strong>
                <?php echo esc_html($membership_id); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .orgman-org-info {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .orgman-org-info h3 {
        margin-top: 0;
        color: #495057;
    }

    .orgman-org-info {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .orgman-org-info h3 {
        margin-top: 0;
        color: #495057;
    }

    .orgman-form-container {
        margin-bottom: 30px;
    }

    .orgman-back-link {
        margin-top: 30px;
        text-align: center;
    }

    .gform_body label {
        font-weight: 600;
    }

    .gform_footer {
        text-align: center;
    }

    .ginput_container_number input[type='number'] {
        border: 2px solid #ddd !important;
        border-radius: 6px !important;
        padding: 12px 16px !important;
        font-size: 16px !important;
        font-weight: 500 !important;
        background-color: #fff !important;
        transition: border-color 0.3s ease, box-shadow 0.3s ease !important;
        width: 100% !important;
        max-width: 200px !important;
    }

    .ginput_container_number input[type='number']:focus {
        border-color: #0073aa !important;
        box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1) !important;
        outline: none !important;
    }

    .ginput_container_number input[type='number']:hover {
        border-color: #0073aa !important;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find the number input field for additional seats
    const numberInput = document.getElementById('input_59_3');
    const form = numberInput ? numberInput.closest('form') : null;

    if (numberInput && form) {
        // Translatable strings
        const messages = {
            validNumber: <?php echo wp_json_encode(esc_html__('Please enter a valid number for additional seats.', 'wicket-acc')); ?>,
            noNegative: <?php echo wp_json_encode(esc_html__('Number of additional seats cannot be negative.', 'wicket-acc')); ?>,
            noZero: <?php echo wp_json_encode(esc_html__('You cannot purchase zero seats. Please enter a number greater than 0 to proceed with your purchase.', 'wicket-acc')); ?>,
            wholeNumber: <?php echo wp_json_encode(esc_html__('Please enter a whole number for additional seats.', 'wicket-acc')); ?>
        };

        // Set minimum attribute to 0
        numberInput.setAttribute('min', '0');
        numberInput.setAttribute('step', '1');

        // Add input validation on change/blur
        numberInput.addEventListener('input', function() {
            // Remove any negative values and empty strings
            if (this.value === '' || parseFloat(this.value) < 0) {
                this.value = '';
            }
            // Ensure it's an integer
            else if (this.value && parseFloat(this.value) !== parseInt(this.value)) {
                this.value = parseInt(this.value);
            }
        });

        numberInput.addEventListener('blur', function() {
            // Convert to number if valid and non-negative
            const value = parseFloat(this.value);
            if (isNaN(value) || value < 0) {
                this.value = '';
            } else {
                this.value = Math.max(0, parseInt(value));
            }
        });

        // Override Gravity Forms submission handler to catch their internal process
        if (typeof gform !== 'undefined' && gform.submission) {
            const originalHandleButtonClick = gform.submission.handleButtonClick;
            gform.submission.handleButtonClick = function(button) {
                const value = parseFloat(numberInput.value);

                // Check if input is empty or not a valid number
                if (isNaN(value) || numberInput.value === '') {
                    alert(messages.validNumber);
                    numberInput.focus();
                    return false;
                }

                // Check if value is negative
                if (value < 0) {
                    alert(messages.noNegative);
                    numberInput.focus();
                    numberInput.value = '';
                    return false;
                }

                // Check if value is 0
                if (value === 0) {
                    alert(messages.noZero);
                    numberInput.focus();
                    return false;
                }

                // Ensure integer value
                if (parseFloat(value) !== parseInt(value)) {
                    alert(messages.wholeNumber);
                    numberInput.focus();
                    return false;
                }

                // If all validation passes, call original function
                return originalHandleButtonClick.call(this, button);
            };
        }

        // Override the submit button onclick with proper GF method call
        const submitButton = form.querySelector('#gform_submit_button_59');
        if (submitButton) {
            submitButton.onclick = function(e) {
                const value = parseFloat(numberInput.value);

                // Check if input is empty or not a valid number
                if (isNaN(value) || numberInput.value === '') {
                    e.preventDefault();
                    e.stopPropagation();
                    alert(messages.validNumber);
                    numberInput.focus();
                    return false;
                }

                // Check if value is negative
                if (value < 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert(messages.noNegative);
                    numberInput.focus();
                    numberInput.value = '';
                    return false;
                }

                // Check if value is 0
                if (value === 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert(messages.noZero);
                    numberInput.focus();
                    return false;
                }

                // Ensure integer value
                if (parseFloat(value) !== parseInt(value)) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert(messages.wholeNumber);
                    numberInput.focus();
                    return false;
                }

                // If all validation passes, let GF handle submission using proper method
                if (typeof window.gform !== 'undefined' && window.gform.submission) {
                    return window.gform.submission.handleButtonClick(this);
                }

                return true; // Allow normal form submission if GF API not available
            };
        }
    }
});
</script>
