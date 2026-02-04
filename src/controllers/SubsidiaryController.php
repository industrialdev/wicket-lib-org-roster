<?php
/**
 * Subsidiary management controller.
 *
 * @package OrgManagement
 */

namespace OrgManagement\Controllers;

use OrgManagement\Services\SubsidiaryService;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles routes related to organization subsidiary management.
 */
class SubsidiaryController extends ApiController {

	/**
	 * @var SubsidiaryService
	 */
	private $subsidiary_service;

	/**
	 * Constructor.
	 *
	 * @param SubsidiaryService $subsidiary_service Service dependency.
	 */
	public function __construct( SubsidiaryService $subsidiary_service ) {
		$this->subsidiary_service = $subsidiary_service;
		$this->namespace          = 'org-management/v1/subsidiaries';
	}

	/**
	 * Register REST routes handled by this controller.
	 */
	public function register_routes() {
		// Route for getting subsidiary list
		register_rest_route( $this->namespace, '/list', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_subsidiary_list' ],
				'permission_callback' => [ $this, 'check_logged_in' ],
			],
		] );

		// Route for adding a subsidiary
		register_rest_route( $this->namespace, '/add', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'add_subsidiary' ],
				'permission_callback' => [ $this, 'check_logged_in' ],
			],
		] );

		// Route for removing a subsidiary
		register_rest_route( $this->namespace, '/remove', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'remove_subsidiary' ],
				'permission_callback' => [ $this, 'check_logged_in' ],
			],
		] );

		// Route for searching subsidiary candidates
		register_rest_route( $this->namespace, '/search', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'search_subsidiary_candidates' ],
				'permission_callback' => [ $this, 'check_logged_in' ],
			],
		] );

		// Route for bulk subsidiary upload
		register_rest_route( $this->namespace, '/bulk-upload', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'bulk_upload_subsidiaries' ],
				'permission_callback' => [ $this, 'check_logged_in' ],
			],
		] );
	}

	/**
	 * Get the rendered subsidiary list partial.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_subsidiary_list( WP_REST_Request $request ) {
		$org_id = sanitize_text_field( $request->get_param( 'org_id' ) );

		if ( empty( $org_id ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'org_id' => '',
				'subsidiaries' => [],
				'notice' => [
					'type'    => 'error',
					'message' => __( 'Organization ID is required.', 'wicket-acc' ),
				],
			] );
		}

		$subsidiaries = $this->subsidiary_service->get_subsidiaries( $org_id );

		if ( is_wp_error( $subsidiaries ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'org_id' => $org_id,
				'subsidiaries' => [],
				'notice' => [
					'type'    => 'error',
					'message' => $subsidiaries->get_error_message(),
				],
			] );
		}

		$view_model = [
			'org_id' => $org_id,
			'subsidiaries' => $subsidiaries,
		];

		return $this->html_response( 'subsidiaries-list', $view_model );
	}

	/**
	 * Add a subsidiary to an organization.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function add_subsidiary( WP_REST_Request $request ) {
		$org_id = sanitize_text_field( $request->get_param( 'org_id' ) );
		$subsidiary_org_id = sanitize_text_field( $request->get_param( 'subsidiary_org_id' ) );

		if ( empty( $org_id ) || empty( $subsidiary_org_id ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => __( 'Organization ID and Subsidiary Organization ID are required.', 'wicket-acc' ),
				],
			] );
		}

		// Verify nonce for security
		$nonce = $request->get_param( '_wpnonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'org_management_subsidiary_add_' . $org_id ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => __( 'Security verification failed. Please try again.', 'wicket-acc' ),
				],
			] );
		}

		$result = $this->subsidiary_service->add_subsidiary( $org_id, $subsidiary_org_id );

		if ( is_wp_error( $result ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => $result->get_error_message(),
				],
			] );
		}

		// Refresh the subsidiary list after successful addition
		$subsidiaries = $this->subsidiary_service->get_subsidiaries( $org_id );

		$view_model = [
			'org_id' => $org_id,
			'subsidiaries' => is_wp_error( $subsidiaries ) ? [] : $subsidiaries,
			'notice' => [
				'type'    => 'success',
				'message' => __( 'Subsidiary added successfully.', 'wicket-acc' ),
			],
		];

		return $this->html_response( 'subsidiaries-list', $view_model );
	}

	/**
	 * Remove a subsidiary from an organization.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function remove_subsidiary( WP_REST_Request $request ) {
		$org_id = sanitize_text_field( $request->get_param( 'org_id' ) );
		$subsidiary_org_id = sanitize_text_field( $request->get_param( 'subsidiary_org_id' ) );

		if ( empty( $org_id ) || empty( $subsidiary_org_id ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => __( 'Organization ID and Subsidiary Organization ID are required.', 'wicket-acc' ),
				],
			] );
		}

		// Verify nonce for security
		$nonce = $request->get_param( '_wpnonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'org_management_subsidiary_remove_' . $org_id ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => __( 'Security verification failed. Please try again.', 'wicket-acc' ),
				],
			] );
		}

		$result = $this->subsidiary_service->remove_subsidiary( $org_id, $subsidiary_org_id );

		if ( is_wp_error( $result ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => $result->get_error_message(),
				],
			] );
		}

		// Refresh the subsidiary list after successful removal
		$subsidiaries = $this->subsidiary_service->get_subsidiaries( $org_id );

		$view_model = [
			'org_id' => $org_id,
			'subsidiaries' => is_wp_error( $subsidiaries ) ? [] : $subsidiaries,
			'notice' => [
				'type'    => 'success',
				'message' => __( 'Subsidiary removed successfully.', 'wicket-acc' ),
			],
		];

		return $this->html_response( 'subsidiaries-list', $view_model );
	}

	/**
	 * Search for organizations that can be added as subsidiaries.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function search_subsidiary_candidates( WP_REST_Request $request ) {
		$org_id = sanitize_text_field( $request->get_param( 'org_id' ) );
		$search_term = sanitize_text_field( $request->get_param( 'search' ) );

		if ( empty( $org_id ) ) {
			return $this->success( [
				'candidates' => [],
				'error' => __( 'Organization ID is required.', 'wicket-acc' ),
			] );
		}

		$candidates = $this->subsidiary_service->search_subsidiary_candidates( $search_term, $org_id );

		return $this->success( [
			'candidates' => $candidates,
			'search_term' => $search_term,
		] );
	}

	/**
	 * Handle bulk subsidiary upload from spreadsheet.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function bulk_upload_subsidiaries( WP_REST_Request $request ) {
		$org_id = sanitize_text_field( $request->get_param( 'org_id' ) );

		if ( empty( $org_id ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => __( 'Organization ID is required.', 'wicket-acc' ),
				],
			] );
		}

		// Verify nonce for security
		$nonce = $request->get_param( '_wpnonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'org_management_subsidiary_bulk_upload_' . $org_id ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => __( 'Security verification failed. Please try again.', 'wicket-acc' ),
				],
			] );
		}

		// Handle file upload from $_FILES
		$uploaded_file = $_FILES['bulk_file'] ?? null;

		if ( ! $uploaded_file || $uploaded_file['error'] !== UPLOAD_ERR_OK ) {
			$error_message = __( 'File upload failed.', 'wicket-acc' );

			if ( $uploaded_file && isset( $uploaded_file['error'] ) ) {
				switch ( $uploaded_file['error'] ) {
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						$error_message = __( 'File size exceeds the maximum allowed size.', 'wicket-acc' );
						break;
					case UPLOAD_ERR_PARTIAL:
						$error_message = __( 'File was only partially uploaded.', 'wicket-acc' );
						break;
					case UPLOAD_ERR_NO_FILE:
						$error_message = __( 'No file was uploaded.', 'wicket-acc' );
						break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$error_message = __( 'Missing temporary folder.', 'wicket-acc' );
						break;
					case UPLOAD_ERR_CANT_WRITE:
						$error_message = __( 'Failed to write file to disk.', 'wicket-acc' );
						break;
					case UPLOAD_ERR_EXTENSION:
						$error_message = __( 'File upload stopped by extension.', 'wicket-acc' );
						break;
				}
			}

			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => $error_message,
				],
			] );
		}

		$result = $this->subsidiary_service->process_bulk_subsidiary_upload( $org_id, $uploaded_file );

		if ( is_wp_error( $result ) ) {
			return $this->html_response( 'subsidiaries-list', [
				'notice' => [
					'type'    => 'error',
					'message' => $result->get_error_message(),
				],
			] );
		}

		// Refresh the subsidiary list after bulk upload
		$subsidiaries = $this->subsidiary_service->get_subsidiaries( $org_id );

		$view_model = [
			'org_id' => $org_id,
			'subsidiaries' => is_wp_error( $subsidiaries ) ? [] : $subsidiaries,
			'notice' => [
				'type'    => 'success',
				'message' => $result['message'] ?? __( 'Bulk upload processed successfully.', 'wicket-acc' ),
			],
		];

		return $this->html_response( 'subsidiaries-list', $view_model );
	}

	/**
	 * Render template partial and wrap in REST response.
	 *
	 * @param string $template Template name (without extension).
	 * @param array  $data     Data for the template.
	 * @return WP_REST_Response
	 */
	private function html_response( $template, array $data ) {
		ob_start();
		if ( ! empty( $data ) ) {
			extract( $data );
		}
		$template_path = dirname( dirname( __FILE__ ) ) . '/templates-partials/' . $template . '.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<p>Template not found: ' . esc_html( $template_path ) . '</p>';
		}
		$html = ob_get_clean();

		$response = new WP_REST_Response( $html );
		$response->header( 'Content-Type', 'text/html' );

		return $response;
	}
}