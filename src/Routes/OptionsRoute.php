<?php
namespace UniqueCoupons\Routes;

use UniqueCoupons\Models\CouponGroup;
use UniqueCoupons\Options;
use UniqueCoupons\Services\PopupService;

class OptionsRoute extends \WP_REST_Controller {
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/options',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_options' ),
					'permission_callback' => array( $this, 'is_user_permitted' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'set_options' ),
					'permission_callback' => array( $this, 'is_user_permitted' ),
					'args'                => $this->get_request_schema(),
				),
				'schema' => array( $this, 'get_response_schema' ),
			)
		);
	}

	/** @param \WP_REST_Request $request */
	public function is_user_permitted( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return WP_Error|\WP_REST_Response
	 */
	public function get_options( $request ) {
		return \rest_ensure_response( Options::get_options() );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return WP_Error|\WP_REST_Response
	 */
	public function set_options( $request ) {
		$options = $request->get_params();
		Options::set_options( $options );
		return \rest_ensure_response( Options::get_options() );
	}

	public function get_request_schema() {
		return array_fill_keys(
			array_keys( Options::$default_options ),
			array( 'type' => 'integer' )
		);
	}

	public function get_response_schema() {
		return array(
			'type'       => 'object',
			'properties' => $this->get_request_schema(),
		);
	}
}
