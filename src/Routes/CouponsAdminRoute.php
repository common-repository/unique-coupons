<?php
namespace UniqueCoupons\Routes;

use UniqueCoupons\Controllers\CouponsAdminRestController;

class CouponsAdminRoute {
	public function __construct( $namespace ) {
		$this->namespace  = $namespace;
		$this->controller = new CouponsAdminRestController();
	}

	public function register_routes() {
		$this->register_route(
			'/',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this->controller, 'get_coupons' ),
					'permission_callback' => array( $this, 'is_user_permitted' ),
					'args'                => $this->get_coupons_arguments_schema(),
				),
				'schema' => array( $this, 'get_coupons_response_schema' ),
			)
		);

		$this->register_route(
			'/create',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this->controller, 'create_coupons' ),
					'permission_callback' => array( $this, 'is_user_permitted' ),
					'args'                => $this->create_coupons_arguments_schema(),
				),
				'schema' => array( $this, 'create_coupons_response_schema' ),
			)
		);

		$this->register_update_route( 'activate' );
		$this->register_update_route( 'deactivate' );
		$this->register_update_route( 'delete' );
	}

	private function register_route( $route, $args = array() ) {
		return register_rest_route(
			$this->namespace,
			'/coupons' . $route,
			$args
		);
	}

	private function register_update_route( $action ) {
		$this->register_route(
			"/$action",
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this->controller, "{$action}_coupons" ),
					'permission_callback' => array( $this, 'is_user_permitted' ),
					'args'                => $this->update_coupons_status_arguments_schema(),
				),
				'schema' => array( $this, 'update_coupons_status_response_schema' ),
			)
		);
	}

	public function is_user_permitted() {
		return current_user_can( 'manage_options' );
	}

	public function get_coupons_arguments_schema() {
		return array(
			'group_id' => array(
				'required' => true,
				'type'     => 'integer',
			),
		);
	}

	public function get_coupons_response_schema() {
		return array(
			'title' => 'coupons',
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'id'         => array( 'type' => 'integer' ),
					'value'      => array( 'type' => 'string' ),
					'expires_at' => array( 'type' => 'integer' ),
					'status'     => array( 'type' => 'string' ),
					'user_id'    => array( 'type' => 'integer' ),
				),
			),
		);
	}

	public function create_coupons_arguments_schema() {
		return array(
			'group_id'      => array(
				'required' => true,
				'type'     => 'integer',
			),
			'coupon_values' => array(
				'required' => true,
				'type'     => 'array',
				'items'    => array( 'type' => 'string' ),
			),
			'expires_at'    => array(
				'required' => true,
				'type'     => 'integer',
			),
		);
	}

	public function create_coupons_response_schema() {
		return array(
			'title' => 'coupon_ids',
			'type'  => 'array',
			'items' => array( 'type' => 'integer' ),
		);
	}

	public function update_coupons_status_arguments_schema() {
		return array(
			'coupon_ids' => array(
				'required' => true,
				'type'     => 'array',
				'items'    => array( 'type' => 'integer' ),
			),
		);
	}

	public function update_coupons_status_response_schema() {
		return array(
			'success' => array( 'type' => 'boolean' ),
		);
	}
}
