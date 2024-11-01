<?php
namespace UniqueCoupons\Controllers;

use UniqueCoupons\Models\CouponGroup;

class PopupPreviewController {
	/** @var CouponGroup */
	private $group;

	public function __construct( int $group_id ) {
		if ( CouponGroup::exists( $group_id ) ) {
			$this->group = new CouponGroup( $group_id );
		}
	}

	public function init_popup() {
		if ( ! $this->group ) {
			return;
		}
		$this->enqueue_template();
		$this->enqueue_assets();
	}

	private function enqueue_template() {
		add_action( 'wp_footer', array( $this->group, 'echo_popup' ) );
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
