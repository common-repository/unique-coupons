<?php
namespace UniqueCoupons\Controllers;

use WP_Error;
use UniqueCoupons\Models\Coupon;
use UniqueCoupons\Models\CouponGroup;

class CouponsAdminRestController {
	/**
	 * @param \WP_REST_Request $request
	 * @return WP_Error|\WP_REST_Response
	 */
	public function get_coupons( $request ) {
		$group_id = $request->get_param( 'group_id' );
		if ( ! CouponGroup::exists( $group_id ) ) {
			return new \WP_Error( 'no_group', 'Could not find group with ID {$group_id} in taxonomy unique_coupon_group.', array( 'status' => 404 ) );
		}
		$group   = new CouponGroup( $group_id );
		$coupons = $group->get_coupons();

		return \rest_ensure_response(
			array_map(
				function( Coupon $coupon ) {
					return array(
						'id'         => $coupon->coupon_id,
						'value'      => $coupon->get_value(),
						'expires_at' => $coupon->get_expires_at(),
						'status'     => $coupon->get_status(),
						'user_id'    => $coupon->get_user_id(),
					);
				},
				$coupons
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 * @return WP_Error|\WP_REST_Response
	 */
	public function create_coupons( $request ) {
		$group_id      = $request->get_param( 'group_id' );
		$coupon_values = $request->get_param( 'coupon_values' );
		$expires_at    = $request->get_param( 'expires_at' );

		if ( ! CouponGroup::exists( $group_id ) ) {
			return new \WP_Error( 'no_group', 'Could not find group with ID {$group_id} in taxonomy unique_coupon_group.', array( 'status' => 404 ) );
		}
		try {
			$coupon_ids = $this->add_coupons( $group_id, $coupon_values, $expires_at );
		} catch ( \Exception $ex ) {
			$this->revert_add_coupons( $coupon_ids );
			return new \WP_Error( 'failed_insert', $ex->getMessage() );
		}
		return \rest_ensure_response( $coupon_ids );
	}

	private function add_coupons( $group_id, $coupon_values, $expires_at ) {
		$coupon_ids = array();
		foreach ( $coupon_values as $coupon_value ) {
			$coupon_ids[] = Coupon::insert(
				array(
					'value'      => $coupon_value,
					'group_id'   => $group_id,
					'expires_at' => $expires_at,
				)
			);
		}
		return $coupon_ids;
	}

	private function revert_add_coupons( $coupon_ids ) {
		foreach ( $coupon_ids as $id ) {
			Coupon::delete( $id );
		}
	}

	public function activate_coupons( $request ) {
		return $this->update_coupons( $request, 'activate' );
	}

	public function deactivate_coupons( $request ) {
		return $this->update_coupons( $request, 'deactivate' );
	}

	public function delete_coupons( $request ) {
		return $this->update_coupons( $request, 'delete' );
	}

	/** @param \WP_REST_Request $request */
	private function update_coupons( $request, $method ) {
		$coupon_ids = $request->get_param( 'coupon_ids' );
		foreach ( $coupon_ids as $coupon_id ) {
			call_user_func( array( Coupon::class, $method ), $coupon_id );
		}
		/** @todo handle errors somehow */
		return \rest_ensure_response( array( 'success' => true ) );
	}
}
