<?php

/**
 * Main orchestrator class for the Organization Management feature.
 *
 * @package OrgManagement
 */

namespace OrgManagement;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) && ! defined( 'WICKET_ORGROSTER_DOINGTESTS' ) ) {
    exit;
}

// Load shared Datastar helpers for modal processing
require_once __DIR__ . '/helpers/DatastarSSE.php';

/**
 * Singleton class for managing the Organization Management feature.
 */
final class OrgMan
{
    /**
     * The single instance of the class.
     *
     * @var OrgMan|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance of the class.
     *
     * @return OrgMan
     */
    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Holds the configuration.
     *
     * @var array
     */
    public $config = [];

    /**
     * Holds the service instances.
     *
     * @var array
     */
    private $services = [];

    /**
     * Holds the controller instances.
     *
     * @var array
     */
    private $controllers = [];

    private $content_map = [];

    /**
     * Initialize the feature, add hooks.
     */
    public function init()
    {
        $this->load_dependencies();
        $this->load_config();
        $this->init_services();
        $this->init_controllers();
        $this->add_hooks();
    }

    /**
     * Load required files.
     */
    private function load_dependencies()
    {
        require_once __DIR__ . '/config/config.php';
        require_once __DIR__ . '/services/ConfigService.php';
        require_once __DIR__ . '/services/strategies/RosterManagementStrategy.php';
        require_once __DIR__ . '/services/strategies/CascadeStrategy.php';
        require_once __DIR__ . '/services/strategies/DirectAssignmentStrategy.php';
        require_once __DIR__ . '/services/strategies/GroupsStrategy.php';
        require_once __DIR__ . '/services/OrganizationService.php';
        require_once __DIR__ . '/services/OrganizationBatchService.php';
        require_once __DIR__ . '/services/MemberService.php';
        require_once __DIR__ . '/services/PersonService.php';
        require_once __DIR__ . '/services/PermissionService.php';
        require_once __DIR__ . '/services/GroupService.php';
        require_once __DIR__ . '/services/ConnectionService.php';
        require_once __DIR__ . '/services/BusinessInfoService.php';
        require_once __DIR__ . '/services/DocumentService.php';
        require_once __DIR__ . '/services/SubsidiaryService.php';
        require_once __DIR__ . '/services/NotificationService.php';
        require_once __DIR__ . '/services/AdditionalSeatsService.php';
        require_once __DIR__ . '/services/MembershipService.php';
        require_once __DIR__ . '/helpers/Helper.php';
        require_once __DIR__ . '/helpers/ConfigHelper.php';
        require_once __DIR__ . '/helpers/RelationshipHelper.php';
        require_once __DIR__ . '/helpers/TemplateHelper.php';
        require_once __DIR__ . '/helpers/GravityFormsHelper.php';
        require_once __DIR__ . '/helpers/PermissionHelper.php';
        require_once __DIR__ . '/controllers/ApiController.php';
        require_once __DIR__ . '/controllers/BusinessInfoController.php';
        require_once __DIR__ . '/controllers/DocumentController.php';
        require_once __DIR__ . '/controllers/SubsidiaryController.php';
        require_once __DIR__ . '/controllers/ConfigurationController.php';
    }

    /**
     * Load the configuration files.
     */
    private function load_config()
    {
        $this->config = \OrgManagement\Config\get_config();
    }

    /**
     * Initialize the services.
     */
    private function init_services()
    {
        $this->services['config'] = new \OrgManagement\Services\ConfigService();
        $this->services['organization'] = new \OrgManagement\Services\OrganizationService();
        $this->services['member'] = new \OrgManagement\Services\MemberService($this->services['config']);
        $this->services['permission'] = new \OrgManagement\Services\PermissionService();
        $this->services['business_info'] = new \OrgManagement\Services\BusinessInfoService();
        $this->services['document'] = new \OrgManagement\Services\DocumentService();
        $this->services['subsidiary'] = new \OrgManagement\Services\SubsidiaryService($this->services['config']);
        $this->services['notification'] = new \OrgManagement\Services\NotificationService();
        $this->services['additional_seats'] = new \OrgManagement\Services\AdditionalSeatsService($this->services['config']);
        $this->services['membership'] = new \OrgManagement\Services\MembershipService();
    }

    /**
     * Initialize the API controllers.
     */
    private function init_controllers()
    {
        $this->controllers['business_info'] = new \OrgManagement\Controllers\BusinessInfoController($this->services['business_info']);
        $this->controllers['document'] = new \OrgManagement\Controllers\DocumentController($this->services['document']);
        $this->controllers['subsidiary'] = new \OrgManagement\Controllers\SubsidiaryController($this->services['subsidiary']);
        $this->controllers['configuration'] = new \OrgManagement\Controllers\ConfigurationController();
    }

    private function add_hooks()
    {
        add_action('rest_api_init', [$this, 'register_api_routes']);
        add_filter('the_content', [$this, 'inject_orgman_content']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Initialize helpers
        add_action('init', [\OrgManagement\Helpers\GravityFormsHelper::class, 'init']);
        add_action('init', [\OrgManagement\Helpers\TemplateHelper::class, 'init']);

        // Initialize configuration controller
        add_action('init', [$this->controllers['configuration'], 'init']);

        // Add WooCommerce order processing hooks
        $this->register_additional_seats_hook('woocommerce_order_status_processing');
        $this->register_additional_seats_hook('woocommerce_order_status_completed');
        $this->register_additional_seats_hook('woocommerce_order_status_on-hold');
        $this->register_additional_seats_hook('woocommerce_payment_complete');

        add_filter('woocommerce_get_return_url', [$this, 'filter_woocommerce_return_url'], 10, 2);

        // Add hooks to transfer user meta to order items
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_additional_seats_data_to_order_item'], 10, 4);
    }

    /**
     * Register an order-processing hook that logs when it fires before running the handler.
     *
     * @param string $hook Hook name.
     * @return void
     */
    private function register_additional_seats_hook($hook)
    {
        add_action($hook, function ($order_id) use ($hook) {
            $logger = wc_get_logger();
            $context = [
                'source' => 'wicket-orgman',
                'hook' => $hook,
                'order_id' => (int) $order_id,
            ];

            $logger->info('[OrgMan] WooCommerce hook fired for additional seats order processing', $context);

            $this->handle_additional_seats_order_processing($order_id);
        }, 10, 1);
    }

    /**
     * Register all API routes.
     */
    public function register_api_routes()
    {
        foreach ($this->controllers as $controller) {
            if (method_exists($controller, 'register_routes')) {
                $controller->register_routes();
            }
        }
    }


    /**
     * Handle additional seats order processing.
     *
     * @param int $order_id The order ID.
     */
    public function handle_additional_seats_order_processing($order_id)
    {
        $order = wc_get_order($order_id);

        $logger = wc_get_logger();
        $context = ['source' => 'wicket-orgman'];

        $logger->info('[OrgMan] Additional seats handler invoked', array_merge($context, [
            'order_id' => $order_id,
        ]));

        if (! $order) {
            $logger->error('[OrgMan] Order not found', array_merge($context, [
                'order_id' => $order_id,
            ]));
            return;
        }

        $logger->debug('[OrgMan] Order loaded', array_merge($context, [
            'order_id' => $order_id,
            'order_status' => $order->get_status(),
            'customer_id' => $order->get_customer_id(),
            'payment_method' => $order->get_payment_method(),
            'transaction_id' => $order->get_transaction_id(),
        ]));

        if ($order->get_meta('additional_seats_processed', true)) {
            $logger->info('[OrgMan] Skipping: already processed', array_merge($context, [
                'order_id' => $order_id,
            ]));
            return;
        }

        // Check if this is an additional seats order
        $additional_seats_service = $this->services['additional_seats'];
        $additional_seats_product_id = $additional_seats_service->get_additional_seats_product();

        if (! $additional_seats_product_id) {
            $logger->error('[OrgMan] Additional seats product not found by SKU', array_merge($context, [
                'order_id' => $order_id,
            ]));
            return;
        }

        $logger->debug('[OrgMan] Additional seats product resolved', array_merge($context, [
            'order_id' => $order_id,
            'additional_seats_product_id' => (int) $additional_seats_product_id,
        ]));

        $has_additional_seats = false;
        $org_uuid = '';
        $membership_id = '';
        $membership_post_id = 0;
        $total_additional_seats = 0;
        $membership_data = null;

        // First, try to get user meta data if available
        $user_id = $order->get_customer_id();
        $user_meta_data = $additional_seats_service->get_purchase_user_meta($user_id);
        if ($user_meta_data) {
            $org_uuid = $user_meta_data['org_uuid'];
            $membership_id = $user_meta_data['membership_id'];
            $membership_data = $user_meta_data['membership_data'];

            $logger->debug('[OrgMan] Loaded additional seats data from user meta', array_merge($context, [
                'order_id' => $order_id,
                'customer_id' => $user_id,
                'org_uuid_present' => $org_uuid !== '',
                'membership_id_present' => $membership_id !== '',
                'membership_data_present' => ! empty($membership_data),
            ]));
        }

        // Check order items for additional seats product
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            if ($product && $product->get_id() === $additional_seats_product_id) {
                $has_additional_seats = true;
                $total_additional_seats += $item->get_quantity();

                $logger->debug('[OrgMan] Found additional seats order item', array_merge($context, [
                    'order_id' => $order_id,
                    'order_item_id' => $item->get_id(),
                    'product_id' => (int) $product->get_id(),
                    'qty' => (int) $item->get_quantity(),
                ]));

                // Get meta data from the item (fallback if session data is missing)
                if (empty($org_uuid)) {
                    $org_uuid = $item->get_meta('org_uuid', true);
                }
                if (empty($membership_id)) {
                    $membership_id = $item->get_meta('membership_id', true);
                }

                if (empty($membership_post_id)) {
                    $membership_post_id = (int) $item->get_meta('membership_post_id_renew', true);
                    if (! $membership_post_id) {
                        $membership_post_id = (int) $item->get_meta('_membership_post_id_renew', true);
                    }
                }

                $logger->debug('[OrgMan] Extracted additional seats item meta', array_merge($context, [
                    'order_id' => $order_id,
                    'order_item_id' => $item->get_id(),
                    'org_uuid_present' => $org_uuid !== '',
                    'membership_id_present' => $membership_id !== '',
                    'membership_post_id' => (int) $membership_post_id,
                ]));
            }
        }

        $logger->debug('[OrgMan] Additional seats item scan complete', array_merge($context, [
            'order_id' => $order_id,
            'has_additional_seats' => $has_additional_seats,
            'total_additional_seats' => (int) $total_additional_seats,
            'org_uuid_present' => $org_uuid !== '',
            'membership_id_present' => $membership_id !== '',
            'membership_post_id' => (int) $membership_post_id,
        ]));

        if (! $membership_post_id) {
            $membership_post_id = (int) $order->get_meta('membership_post_id_renew', true);
            if (! $membership_post_id) {
                $membership_post_id = (int) $order->get_meta('_membership_post_id_renew', true);
            }
        }

        $logger->debug('[OrgMan] Membership post id after order meta fallback', array_merge($context, [
            'order_id' => $order_id,
            'membership_post_id' => (int) $membership_post_id,
        ]));

        if (! $has_additional_seats || empty($org_uuid) || (empty($membership_id) && empty($membership_post_id))) {
            $logger->error('[OrgMan] Invalid additional seats order data', [
                'source' => 'wicket-orgman',
                'order_id' => $order_id,
                'has_additional_seats' => $has_additional_seats,
                'org_uuid_present' => $org_uuid !== '',
                'membership_id_present' => $membership_id !== '',
                'membership_post_id' => (int) $membership_post_id,
                'total_additional_seats' => (int) $total_additional_seats,
            ]);
            return;
        }

        if (! $membership_post_id && ! empty($membership_id)) {
            $logger->debug('[OrgMan] Searching membership post by membership_wicket_uuid', array_merge($context, [
                'order_id' => $order_id,
                'membership_id' => $membership_id,
            ]));
            $query = new \WP_Query([
                'posts_per_page' => 1,
                'post_type' => 'wicket_membership',
                'post_status' => 'any',
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => 'membership_wicket_uuid',
                        'value' => $membership_id,
                        'compare' => '=',
                    ],
                ],
            ]);

            if (! empty($query->posts[0])) {
                $membership_post_id = (int) $query->posts[0];
            }

            $logger->debug('[OrgMan] Membership post query result', array_merge($context, [
                'order_id' => $order_id,
                'membership_post_id' => (int) $membership_post_id,
                'found' => (bool) $membership_post_id,
            ]));
        }

        if (! $membership_post_id) {
            $logger->error('[OrgMan] Unable to locate membership post for additional seats order', [
                'source' => 'wicket-orgman',
                'order_id' => $order_id,
                'membership_id' => $membership_id,
                'org_uuid' => $org_uuid,
            ]);
            return;
        }

        $subscription_id = (int) get_post_meta($membership_post_id, 'membership_subscription_id', true);
        if (! $subscription_id) {
            $logger->error('[OrgMan] Membership post missing membership_subscription_id', [
                'source' => 'wicket-orgman',
                'order_id' => $order_id,
                'membership_post_id' => $membership_post_id,
            ]);
            return;
        }

        $logger->debug('[OrgMan] Subscription linkage resolved', array_merge($context, [
            'order_id' => $order_id,
            'membership_post_id' => (int) $membership_post_id,
            'subscription_id' => (int) $subscription_id,
        ]));

        $current_seats = (int) get_post_meta($membership_post_id, 'org_seats', true);
        $new_seats = $current_seats + (int) $total_additional_seats;

        $logger->info('[OrgMan] Seat calculation', array_merge($context, [
            'order_id' => $order_id,
            'membership_post_id' => (int) $membership_post_id,
            'subscription_id' => (int) $subscription_id,
            'current_seats' => (int) $current_seats,
            'additional_seats' => (int) $total_additional_seats,
            'new_seats' => (int) $new_seats,
        ]));

        // If no membership data in session, try to reconstruct it
        if (! $membership_data) {
            $logger->debug('[OrgMan] Reconstructing membership data for MDP payload', array_merge($context, [
                'order_id' => $order_id,
                'org_uuid_present' => $org_uuid !== '',
                'membership_id_present' => $membership_id !== '',
            ]));
            $membership_data = $additional_seats_service->get_membership_data_for_mdp($org_uuid, $membership_id);
        }

        // Store comprehensive data in order meta for MDP processing
        if ($membership_data) {
            $order->update_meta_data('orgman_membership_data', $membership_data);
            $logger->debug('[OrgMan] Stored membership data on order for MDP', array_merge($context, [
                'order_id' => $order_id,
                'membership_data_keys' => is_array($membership_data) ? array_keys($membership_data) : null,
            ]));
        } else {
            $logger->warning('[OrgMan] Missing membership data for MDP update', array_merge($context, [
                'order_id' => $order_id,
                'org_uuid_present' => $org_uuid !== '',
                'membership_id_present' => $membership_id !== '',
            ]));
        }

        $subscription = function_exists('wcs_get_subscription') ? wcs_get_subscription($subscription_id) : null;
        if (! $subscription) {
            $logger->error('[OrgMan] Subscription not found for membership post', [
                'source' => 'wicket-orgman',
                'order_id' => $order_id,
                'membership_post_id' => $membership_post_id,
                'subscription_id' => $subscription_id,
            ]);
            return;
        }

        update_post_meta($membership_post_id, 'org_seats', $new_seats);

        $logger->info('[OrgMan] Updated membership post org_seats', array_merge($context, [
            'order_id' => $order_id,
            'membership_post_id' => (int) $membership_post_id,
            'org_seats' => (int) $new_seats,
        ]));

        if (class_exists('\\Wicket_Memberships\\Membership_Controller')) {
            try {
                $controller = new \Wicket_Memberships\Membership_Controller();
                $controller->amend_membership_json($membership_post_id, [
                    'membership_seats' => $new_seats,
                ]);

                $logger->info('[OrgMan] Amended membership json after additional seats', array_merge($context, [
                    'order_id' => $order_id,
                    'membership_post_id' => (int) $membership_post_id,
                    'new_seats' => (int) $new_seats,
                ]));
            } catch (\Throwable $e) {
                $logger->error('[OrgMan] Failed to amend membership json after additional seats purchase: ' . $e->getMessage(), [
                    'source' => 'wicket-orgman',
                    'order_id' => $order_id,
                    'membership_post_id' => $membership_post_id,
                ]);
            }
        }

        $membership_product_id = (int) get_post_meta($membership_post_id, 'membership_product_id', true);
        if ($membership_product_id) {
            $updated_subscription_item = false;
            foreach ($subscription->get_items() as $subscription_item) {
                $item_product_id = (int) $subscription_item->get_product_id();
                if ($item_product_id === $membership_product_id) {
                    $subscription_item->set_quantity($new_seats);
                    $updated_subscription_item = true;
                    $logger->info('[OrgMan] Updated subscription item quantity', array_merge($context, [
                        'order_id' => $order_id,
                        'subscription_id' => (int) $subscription_id,
                        'membership_product_id' => (int) $membership_product_id,
                        'subscription_item_id' => $subscription_item->get_id(),
                        'new_qty' => (int) $new_seats,
                    ]));
                }
            }

            if (! $updated_subscription_item) {
                $logger->warning('[OrgMan] Did not find matching subscription item to update quantity', array_merge($context, [
                    'order_id' => $order_id,
                    'subscription_id' => (int) $subscription_id,
                    'membership_product_id' => (int) $membership_product_id,
                ]));
            }

            $subscription->update_meta_data('seat_limit', $new_seats);
            $subscription->calculate_totals(false);
            $subscription->save();

            $logger->info('[OrgMan] Saved subscription after seat update', array_merge($context, [
                'order_id' => $order_id,
                'subscription_id' => (int) $subscription_id,
                'seat_limit' => (int) $new_seats,
            ]));
        } else {
            $logger->warning('[OrgMan] Membership product id missing; cannot update subscription item quantity', array_merge($context, [
                'order_id' => $order_id,
                'membership_post_id' => (int) $membership_post_id,
                'subscription_id' => (int) $subscription_id,
            ]));
        }

        $mdp_membership_id = $membership_id;
        if (empty($mdp_membership_id)) {
            $mdp_membership_id = (string) get_post_meta($membership_post_id, 'membership_wicket_uuid', true);
        }

        if (! empty($mdp_membership_id)) {
            $logger->info('[OrgMan] Updating MDP max_assignments', array_merge($context, [
                'order_id' => $order_id,
                'mdp_membership_id' => $mdp_membership_id,
                'new_max_assignments' => (int) $new_seats,
            ]));

            $mdp_updated = $additional_seats_service->update_mdp_membership_max_assignments($mdp_membership_id, $new_seats);
            $logger->info('[OrgMan] MDP update result', array_merge($context, [
                'order_id' => $order_id,
                'mdp_membership_id' => $mdp_membership_id,
                'success' => (bool) $mdp_updated,
            ]));
        } else {
            $logger->warning('[OrgMan] Missing membership UUID for MDP update', array_merge($context, [
                'order_id' => $order_id,
                'membership_post_id' => (int) $membership_post_id,
            ]));
        }

        // Add order meta for tracking
        $order->update_meta_data('additional_seats_processed', true);
        $order->update_meta_data('additional_seats_count', $total_additional_seats);
        $order->update_meta_data('org_uuid', $org_uuid);
        $order->update_meta_data('membership_id', $membership_id);
        $order->update_meta_data('membership_post_id_renew', $membership_post_id);
        $order->save();

        $logger->info('[OrgMan] Stored additional seats tracking meta on order', array_merge($context, [
            'order_id' => $order_id,
            'additional_seats_processed' => true,
            'additional_seats_count' => (int) $total_additional_seats,
            'org_uuid_present' => $org_uuid !== '',
            'membership_id_present' => $membership_id !== '',
            'membership_post_id' => (int) $membership_post_id,
            'subscription_id' => (int) $subscription_id,
        ]));

        // Clear user meta data after successful processing
        $additional_seats_service->clear_purchase_user_meta($user_id);

        $logger->debug('[OrgMan] Cleared purchase user meta', array_merge($context, [
            'order_id' => $order_id,
            'customer_id' => (int) $user_id,
        ]));

        $logger->info('[OrgMan] Additional seats order processed successfully', [
            'source' => 'wicket-orgman',
            'order_id' => $order_id,
            'subscription_id' => $subscription->get_id(),
            'additional_seats' => $total_additional_seats,
            'org_uuid' => $org_uuid,
            'membership_id' => $membership_id,
            'membership_post_id' => $membership_post_id,
            'previous_seats' => $current_seats,
            'new_seats' => $new_seats,
        ]);
    }

    public function filter_woocommerce_return_url($return_url, $order)
    {
        $logger = wc_get_logger();
        $context = ['source' => 'wicket-orgman'];

        if (! $order || ! is_object($order)) {
            return $return_url;
        }

        $logger->debug('[OrgMan] woocommerce_get_return_url invoked', array_merge($context, [
            'order_id' => is_callable([$order, 'get_id']) ? (int) $order->get_id() : null,
            'order_status' => is_callable([$order, 'get_status']) ? (string) $order->get_status() : null,
            'return_url' => (string) $return_url,
        ]));

        if ($order->get_meta('additional_seats_processed', true)) {
            $target_url = $this->get_organization_members_url_from_order($order) ?: $return_url;
            $logger->info('[OrgMan] Return URL overridden (already processed additional seats)', array_merge($context, [
                'order_id' => (int) $order->get_id(),
                'target_url' => (string) $target_url,
            ]));
            return $target_url;
        }

        if (! $this->order_has_additional_seats($order)) {
            return $return_url;
        }

        $target_url = $this->get_organization_members_url_from_order($order) ?: $return_url;
        $logger->info('[OrgMan] Return URL overridden (additional seats order)', array_merge($context, [
            'order_id' => (int) $order->get_id(),
            'target_url' => (string) $target_url,
        ]));
        return $target_url;
    }

    private function order_has_additional_seats($order)
    {
        $logger = wc_get_logger();
        $context = ['source' => 'wicket-orgman'];

        $additional_seats_service = $this->services['additional_seats'] ?? null;
        if (! $additional_seats_service) {
            $logger->error('[OrgMan] Additional seats service missing; cannot detect product', array_merge($context, [
                'order_id' => is_callable([$order, 'get_id']) ? (int) $order->get_id() : null,
            ]));
            return false;
        }

        $product_id = $additional_seats_service->get_additional_seats_product();
        if (! $product_id) {
            $logger->error('[OrgMan] Additional seats product not found; cannot detect additional seats order', array_merge($context, [
                'order_id' => is_callable([$order, 'get_id']) ? (int) $order->get_id() : null,
            ]));
            return false;
        }

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && (int) $product->get_id() === (int) $product_id) {
                $logger->debug('[OrgMan] Additional seats product detected on order', array_merge($context, [
                    'order_id' => (int) $order->get_id(),
                    'order_item_id' => $item->get_id(),
                    'product_id' => (int) $product_id,
                ]));
                return true;
            }
        }

        return false;
    }

    private function get_organization_members_url_from_order($order)
    {
        $logger = wc_get_logger();
        $context = ['source' => 'wicket-orgman'];

        $org_uuid = (string) $order->get_meta('org_uuid', true);
        if ($org_uuid === '') {
            foreach ($order->get_items() as $item) {
                $org_uuid = (string) $item->get_meta('org_uuid', true);
                if ($org_uuid !== '') {
                    break;
                }
            }
        }

        $logger->debug('[OrgMan] Resolved org_uuid for return URL', array_merge($context, [
            'order_id' => is_callable([$order, 'get_id']) ? (int) $order->get_id() : null,
            'org_uuid_present' => $org_uuid !== '',
        ]));

        // Get WPML-aware URL for organization-members page
        $base_url = Helpers\Helper::get_my_account_page_url('organization-members', '/my-account/organization-members/');

        $logger->debug('[OrgMan] Resolved base organization-members URL', array_merge($context, [
            'order_id' => is_callable([$order, 'get_id']) ? (int) $order->get_id() : null,
            'base_url' => (string) $base_url,
        ]));

        if ($org_uuid !== '') {
            $url = add_query_arg('org_uuid', $org_uuid, $base_url);
            $logger->debug('[OrgMan] Built organization-members return URL', array_merge($context, [
                'order_id' => is_callable([$order, 'get_id']) ? (int) $order->get_id() : null,
                'url' => (string) $url,
            ]));
            return $url;
        }

        return $base_url;
    }

    /**
     * Inject OrgMan content after the_content on specific my-account pages.
     *
     * @param string $content The original content.
     * @return string Modified content with OrgMan content appended.
     */
    public function inject_orgman_content($content)
    {
        if (! $this->is_orgman_screen() || ! in_the_loop() || is_admin()) {
            return $content;
        }

        $slug = $this->get_current_page_slug();
        $content_map = $this->get_content_map();

        if (isset($content_map[$slug])) {
            // Include notifications container
            ob_start();
            include_once dirname(__DIR__) . '/templates-partials/notifications-container.php';
            $notifications = ob_get_clean();

            // Get the OrgMan content
            ob_start();
            include $content_map[$slug];
            $orgman_content = ob_get_clean();

            if ($slug === 'organization-profile' || $slug === 'supplemental-members') {
                // For organization-profile and supplemental-members we need the OrgMan content
                // to appear before the post content to match legacy layout.
                return $notifications . $orgman_content . $content;
            }

            return $content . $notifications . $orgman_content;
        }

        return $content;
    }

    /**
     * Enqueue shared assets for OrgMan pages.
     */
    public function enqueue_assets()
    {
        $is_orgman = $this->is_orgman_screen();

        if (! $is_orgman) {
            return;
        }

        $base_uri = $this->get_base_uri();
        $base_path = $this->get_base_path();

        $css_file_path = $base_path . '/public/css/modern-orgman-compiled.css';
        $css_version = file_exists($css_file_path) ? filemtime($css_file_path) : '1.0.0';
        wp_enqueue_style('orgman-modern', $base_uri . 'public/css/modern-orgman-compiled.css', [], $css_version);

        $datastar_error_path = $base_path . '/public/js/datastar-error-handler.js';
        $datastar_error_version = file_exists($datastar_error_path) ? filemtime($datastar_error_path) : '1.0.0';
        wp_enqueue_script('orgman-datastar-error-handler', $base_uri . 'public/js/datastar-error-handler.js', [], $datastar_error_version, false);

        // Load Datastar from CDN as module script
        $datastar_version = '1.0.0-RC.7';
        $datastar_src = 'https://cdn.jsdelivr.net/gh/starfederation/datastar@' . $datastar_version . '/bundles/datastar.js';
        wp_enqueue_script_module('wicket-datastar', $datastar_src, [], $datastar_version);
    }

    /**
     * Resolve the base path for the org roster library.
     *
     * @return string
     */
    private function get_base_path(): string
    {
        $base_path = dirname(__DIR__);
        return (string) apply_filters('wicket/acc/orgman/base_path', $base_path);
    }

    /**
     * Resolve the base URL for the org roster library assets.
     *
     * @return string
     */
    private function get_base_uri(): string
    {
        $base_path = $this->get_base_path();
        $content_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '';
        $base_uri = trailingslashit(content_url(''));

        if ($content_dir && strpos($base_path, $content_dir) === 0) {
            $relative_path = ltrim(str_replace($content_dir, '', $base_path), '/');
            $base_uri = trailingslashit(content_url($relative_path));
        }

        return trailingslashit((string) apply_filters('wicket/acc/orgman/base_url', $base_uri));
    }

    /**
     * Determine if the current request targets an OrgMan-managed page.
     *
     * @return bool
     */
    private function is_orgman_screen()
    {
        if (! is_singular('my-account')) {
            return false;
        }

        $slug = $this->get_current_page_slug();

        return isset($this->get_content_map()[$slug]);
    }

    /**
     * Get the slug for the current My Account page request.
     *
     * @return string
     */
    private function get_current_page_slug()
    {
        $post = get_queried_object();

        if ($post instanceof \WP_Post) {
            return (string) $post->post_name;
        }

        return '';
    }


    /**
     * Map My Account slugs to content-only template paths for injection.
     *
     * @return array
     */
    private function get_content_map()
    {
        if (! empty($this->content_map)) {
            return $this->content_map;
        }

        $base_path = dirname(__DIR__);

        $this->content_map = [
            'organization-management'           => $base_path . '/templates/content-organization-index.php',
            'organization-profile'              => $base_path . '/templates/content-organization-profile.php',
            'organization-members'              => $base_path . '/templates/content-organization-members.php',
            'supplemental-members'              => $base_path . '/templates/content-supplemental-members.php',
        ];

        return $this->content_map;
    }

    /**
     * Add additional seats data to order item from user meta.
     *
     * @param \WC_Order_Item_Product $item The order item.
     * @param string $cart_item_key The cart item key.
     * @param array $values The cart item values.
     * @param \WC_Order $order The order object.
     */
    public function add_additional_seats_data_to_order_item($item, $cart_item_key, $values, $order)
    {
        $additional_seats_service = $this->services['additional_seats'];
        $additional_seats_product_id = $additional_seats_service->get_additional_seats_product();

        if (! $additional_seats_product_id) {
            return;
        }

        // Check if this is an additional seats product
        $product = $item->get_product();
        if (! $product || $product->get_id() !== $additional_seats_product_id) {
            return;
        }

        // Get user meta data
        $user_id = $order->get_customer_id();
        $user_meta_data = $additional_seats_service->get_purchase_user_meta($user_id);

        if (! $user_meta_data) {
            $logger = wc_get_logger();
            $logger->warning('[OrgMan] No user meta data found for additional seats order item', [
                'source' => 'wicket-orgman',
                'user_id' => $user_id,
                'order_id' => $order->get_id(),
                'item_id' => $item->get_id()
            ]);
            return;
        }

        // Store data in order item meta
        $item->update_meta_data('org_uuid', $user_meta_data['org_uuid']);
        $item->update_meta_data('_org_uuid', $user_meta_data['org_uuid']);
        $item->update_meta_data('membership_id', $user_meta_data['membership_id']);
        $item->update_meta_data('current_seats', $user_meta_data['membership_data']['membership']['current_max_assignments'] ?? 1);

        if (! empty($values['membership_post_id_renew'])) {
            $item->update_meta_data('membership_post_id_renew', (int) $values['membership_post_id_renew']);
            $item->update_meta_data('_membership_post_id_renew', (int) $values['membership_post_id_renew']);
        }

        $logger = wc_get_logger();
        $logger->info('[OrgMan] Added additional seats data to order item', [
            'source' => 'wicket-orgman',
            'user_id' => $user_id,
            'order_id' => $order->get_id(),
            'item_id' => $item->get_id(),
            'org_uuid' => $user_meta_data['org_uuid'],
            'membership_id' => $user_meta_data['membership_id']
        ]);
    }

    /**
     * Clear organization cache for a specific user.
     *
     * @param string $user_uuid The user UUID to clear cache for.
     * @return void
     */
    public function clear_user_org_cache($user_uuid)
    {
        if (empty($user_uuid)) {
            return;
        }

        // Clear user organizations cache
        $cache_key = 'orgman_user_orgs_' . md5($user_uuid . '_' . $user_uuid);
        delete_transient($cache_key);

        // Clear all active organization caches for this user
        global $wpdb;
        $like_pattern = $wpdb->esc_like('orgman_active_orgs_' . md5($user_uuid . '_' . $user_uuid . '_')) . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                $like_pattern
            )
        );

        // Clear membership-related caches for this user
        $like_pattern = $wpdb->esc_like('orgman_membership_' . md5($user_uuid . '_')) . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                $like_pattern
            )
        );
    }

    /**
     * Clear members cache for a specific organization.
     *
     * @param string $membership_uuid The membership UUID to clear cache for.
     * @return void
     */
    public function clear_members_cache($membership_uuid)
    {
        if (empty($membership_uuid)) {
            return;
        }

        $this->services['member']->clear_members_cache($membership_uuid);
    }

    /**
     * Clear all organization management transients.
     *
     * @return void
     */
    public function clear_all_org_cache()
    {
        global $wpdb;

        // Clear all orgman transients
        $like_pattern = $wpdb->esc_like('_transient_orgman_') . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                $like_pattern,
                str_replace('_transient_', '_transient_timeout_', $like_pattern)
            )
        );

        wc_get_logger()->info('[OrgMan] Cleared all organization management cache', ['source' => 'wicket-orgman']);
    }
}
