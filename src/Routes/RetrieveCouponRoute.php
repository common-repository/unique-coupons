<?php
namespace UniqueCoupons\Routes;

use UniqueCoupons\Models\CouponGroup;
use UniqueCoupons\Services\PopupService;

class RetrieveCouponRoute extends \WP_REST_Controller {
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/retrieve-coupon',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_request' ),
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
	public function handle_request( $request ) {
		$group_id = $request->get_param( 'group_id' );
		$group    = new CouponGroup( $group_id );

		$popup_service = new PopupService();

		try {
			$coupon = $popup_service->retrieve_coupon_for( $group );
			return \rest_ensure_response(
				array(
					'value'      => $coupon->get_value(),
					'expires_at' => $coupon->get_expires_at(),
				)
			);
		} catch ( \Exception $ex ) {
			return new \WP_Error( 'failed_retrieve', $ex->getMessage() );
		}
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
			'title'       => 'coupon',
			'description' => 'The retrieved coupon',
			'type'        => 'object',
			'properties'  => array(
				'value'      => array( 'type' => 'string' ),
				'expires_at' => array( 'type' => 'integer' ),
			),
		);
	}
}
