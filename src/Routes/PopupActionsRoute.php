<?php
namespace UniqueCoupons\Routes;

use UniqueCoupons\Models\CouponGroup;
use UniqueCoupons\Services\PopupService;

class PopupActionsRoute extends \WP_REST_Controller {
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/on-popup-open',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'on_popup_open' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_request_schema(),
				),
				'schema' => array( $this, 'get_response_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/on-popup-close',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'on_popup_close' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_request_schema(),
				),
				'schema' => array( $this, 'get_response_schema' ),
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return WP_Error|\WP_REST_Response
	 */
	public function on_popup_open( $request ) {
		$group_id = $request->get_param( 'group_id' );
		$group    = new CouponGroup( $group_id );

		$popup_service = new PopupService();
		$popup_service->on_popup_open( $group );

		return \rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return WP_Error|\WP_REST_Response
	 */
	public function on_popup_close( $request ) {
		$group_id = $request->get_param( 'group_id' );
		$group    = new CouponGroup( $group_id );

		$popup_service = new PopupService();
		$popup_service->on_popup_close( $group );

		return \rest_ensure_response( array( 'success' => true ) );
	}

	public function get_request_schema() {
		return array(
			'group_id' => array(
				'required' => true,
				'type'     => 'integer',
			),
		);
	}

	public function get_response_schema() {
		return array(
			'success' => array( 'type' => 'boolean' ),
		);
	}
}
