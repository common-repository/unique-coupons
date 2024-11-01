<?php
namespace UniqueCoupons\Services;

use UniqueCoupons\Models\User;
use UniqueCoupons\Models\CouponGroup;
use UniqueCoupons\Utils;

/**
 * Handles interactions with coupons for public users.
 */
class PopupService {
	/** @var User */
	public $user;

	public function __construct( User $user = null ) {
		$this->user = $user ?? new User();
	}

	/**
	 * Find a group to display (regarding previously retrieved/displayed coupons)
	 * for users who can and want to receive coupons (in general).
	 *
	 * @todo allow users to ignore specific groups
	 * @todo improve algorithm for determining the best group
	 * @throws \Exception No CouponGroup found.
	 */
	public function get_possible_group(): CouponGroup {
		if ( ! $this->user->can_receive_coupons() ) {
			throw new \Exception( 'User cannot receive coupons' );
		}

		$active_groups = CouponGroup::get_active_groups();

		$possible_group = Utils::array_find(
			$active_groups,
			function( CouponGroup $group ) {
				return ! $this->user->has_recent_popup_for_group( $group )
					&& ! $this->user->has_recent_retrieval_for_group( $group )
					&& $group->has_distributable_coupons()
					&& $group->has_unlocked_coupons();
			}
		);

		if ( ! $possible_group ) {
			throw new \Exception( 'No possible group found' );
		}
		return $possible_group;
	}

	public function retrieve_coupon_for( CouponGroup $group ) {
		if ( ! $this->user->is_authorized_for_coupons() ) {
			throw new \Exception( 'User cannot receive coupons' );
		}

		$coupon = $group->get_distributable_coupon();

		$this->user->record_retrieval(
			array(
				'coupon_id' => $coupon->coupon_id,
				'group_id'  => $group->group_id,
				'timestamp' => time(),
			)
		);

		return $coupon;
	}

	public function on_popup_open( CouponGroup $group ) {
		$this->user->record_popup(
			array(
				'group_id'  => $group->group_id,
				'timestamp' => time(),
			)
		);
	}

	public function on_popup_close( CouponGroup $group ) {
		$group->release_lock_for( $this->user );
	}
}
