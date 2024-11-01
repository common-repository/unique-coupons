<?php
namespace UniqueCoupons\Controllers;

use UniqueCoupons\Models\CouponGroup;
use UniqueCoupons\Models\User;
use UniqueCoupons\Services\PopupService;

class PopupController {
	public function init_popup() {
		try {
			$user          = new User();
			$popup_service = new PopupService( $user );
			$group         = $popup_service->get_possible_group();
			$this->enqueue_popup( $group, $user );
		} catch ( \Exception $ex ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// We could not find a group to display, so just do nothing.
		}
	}

	private function enqueue_popup( CouponGroup $group, User $user ) {
		$group->lock_coupon_for( $user );

		$this->enqueue_template( $group );
		$this->enqueue_assets();
	}

	private function enqueue_template( CouponGroup $group ) {
		add_action( 'wp_footer', array( $group, 'echo_popup' ) );
	}

	private function enqueue_assets() {
		add_action(
			'wp_enqueue_scripts',
			function() {
				wp_enqueue_style( 'unique-coupons-popup' );
				wp_enqueue_script( 'unique-coupons-popup' );
			}
		);
	}
}
