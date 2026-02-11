<?php

/**
 * Business information controller.
 */

namespace OrgManagement\Controllers;

use OrgManagement\Services\BusinessInfoService;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles routes related to organization business information.
 */
class BusinessInfoController extends ApiController
{
    /**
     * @var BusinessInfoService
     */
    private $business_info_service;

    /**
     * Constructor.
     *
     * @param BusinessInfoService $business_info_service Service dependency.
     */
    public function __construct(BusinessInfoService $business_info_service)
    {
        $this->business_info_service = $business_info_service;
        $this->namespace = 'org-management/v1/business';
    }

    /**
     * Register REST routes handled by this controller.
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/info', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_business_info'],
                'permission_callback' => [$this, 'check_logged_in'],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'post_business_info'],
                'permission_callback' => [$this, 'check_logged_in'],
            ],
        ]);
    }

    /**
     * Get the rendered business info partial.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_business_info(WP_REST_Request $request)
    {
        $org_id = sanitize_text_field($request->get_param('org_id'));

        if (empty($org_id)) {
            return $this->html_response('business-info', [
                'org_id'  => '',
                'header'  => ['name' => '', 'address' => '', 'email' => '', 'phone' => ''],
                'sections' => [],
                'state'   => [],
                'notice'  => [
                    'type'    => 'error',
                    'message' => __('Organization ID is required.', 'wicket-acc'),
                ],
            ]);
        }

        $view_model = $this->build_view_model($org_id);

        return $this->html_response('business-info', $view_model);
    }

    /**
     * Handle posted changes for business information.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function post_business_info(WP_REST_Request $request)
    {
        $org_id = sanitize_text_field($request->get_param('org_id'));

        if (empty($org_id)) {
            return $this->html_response('business-info', [
                'notice' => [
                    'type'    => 'error',
                    'message' => __('Organization ID is required.', 'wicket-acc'),
                ],
            ]);
        }

        // Verify nonce for security
        $nonce = $request->get_param('_wpnonce');
        if (!$nonce || !wp_verify_nonce($nonce, 'org_management_business_info_' . $org_id)) {
            return $this->html_response('business-info', [
                'notice' => [
                    'type'    => 'error',
                    'message' => __('Security verification failed. Please try again.', 'wicket-acc'),
                ],
            ]);
        }

        $result = $this->business_info_service->update_sections($org_id, $request->get_params());

        $notice = [
            'type'    => 'success',
            'message' => __('Business information updated successfully.', 'wicket-acc'),
        ];

        if (is_wp_error($result)) {
            $notice = [
                'type'    => 'error',
                'message' => $result->get_error_message(),
            ];
        }

        $view_model = $this->build_view_model($org_id);
        $view_model['notice'] = $notice;

        return $this->html_response('business-info', $view_model);
    }

    /**
     * Build the data structure required by the business info partial.
     *
     * @param string $org_id Organization UUID.
     * @return array
     */
    private function build_view_model($org_id)
    {
        return [
            'org_id'  => $org_id,
            'header'  => $this->business_info_service->get_organization_header($org_id),
            'sections' => $this->business_info_service->get_sections_config(),
            'state'   => $this->business_info_service->get_sections_state($org_id),
        ];
    }

    /**
     * Render template partial and wrap in REST response.
     *
     * @param string $template Template name (without extension).
     * @param array  $data     Data for the template.
     * @return WP_REST_Response
     */
    private function html_response($template, array $data)
    {
        ob_start();
        extract($data);
        include __DIR__ . "/../../templates-partials/{$template}.php";
        $html = ob_get_clean();

        $response = new WP_REST_Response($html);
        $response->header('Content-Type', 'text/html');

        return $response;
    }
}
