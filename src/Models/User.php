<?php
namespace UniqueCoupons\Models;

use UniqueCoupons\Options;
use UniqueCoupons\Utils;

class User {
	const RETRIEVALS_META_KEY  = 'unique_coupons_retrievals';
	const GROUPS_DATA_META_KEY = 'unique_coupons_groups_data';

	/**
	 * ID of the WordPress user.
	 *
	 * @var int
	 */
	public $user_id;

	public function __construct( int $user_id = null ) {
		$this->user_id = $user_id ?? get_current_user_id();
	}

	public function can_receive_coupons() {
		return $this->is_authorized_for_coupons()
			&& ! $this->has_opted_out_from_coupons()
			&& ! $this->has_recent_retrieval()
			&& ! $this->has_recent_popup();
	}

	public function is_authorized_for_coupons() {
		$is_user_logged_in = $this->user_id > 0;
		return $is_user_logged_in
			&& apply_filters( 'unique_coupons_user_is_authorized_for_coupons', true, $this->user_id );
	}

	/** @todo add field to user meta */
	public function has_opted_out_from_coupons() {
		return false;
	}

	public function get_retrievals() {
		return get_user_meta( $this->user_id, self::RETRIEVALS_META_KEY );
	}

	public function get_groups_data() {
		return get_user_meta( $this->user_id, self::GROUPS_DATA_META_KEY );
	}

	public function get_group_data( CouponGroup $group ) {
		return Utils::array_find(
			$this->get_groups_data(),
			function( $group_data ) use ( $group ) {
				return $group_data['group_id'] === $group->group_id;
			},
			array()
		);
	}

	public function has_recent_retrieval() {
		return $this->has_recent_timestamp(
			'last_retrieval_timestamp',
			Options::get_seconds_between_any_retrieval()
		);
	}

	public function has_recent_popup() {
		return $this->has_recent_timestamp(
			'last_popup_timestamp',
			Options::get_seconds_between_any_popup()
		);
	}

	private function has_recent_timestamp( $key, $time_delta ) {
		$timestamps = array_column( $this->get_groups_data(), $key );
		if ( empty( $timestamps ) ) {
			return false;
		}
		return time() < max( $timestamps ) + $time_delta;
	}

	public function has_recent_popup_for_group( $group ) {
		return $this->has_recent_timestamp_for_group(
			$group,
			'last_popup_timestamp',
			Options::get_seconds_between_same_popup()
		);
	}

	public function has_recent_retrieval_for_group( $group ) {
		return $this->has_recent_timestamp_for_group(
			$group,
			'last_retrieval_timestamp',
			Options::get_seconds_between_same_retrieval()
		);
	}

	private function has_recent_timestamp_for_group( $group, $key, $time_delta ) {
		$group_data = $this->get_group_data( $group );
		if ( ! isset( $group_data[ $key ] ) ) {
			return false;
		}
		return time() < $group_data[ $key ] + $time_delta;
	}

	public function record_retrieval( $data ) {
		list(
			'coupon_id' => $coupon_id,
			'group_id' => $group_id,
			'timestamp' => $timestamp
		) = $data;

		add_user_meta(
			$this->user_id,
			self::RETRIEVALS_META_KEY,
			array(
				'coupon_id'    => $coupon_id,
				'retrieved_at' => $timestamp,
			)
		);
		$this->update_group_meta(
			array(
				'group_id'                 => $group_id,
				'last_retrieval_timestamp' => $timestamp,
			)
		);

		$coupon = new Coupon( $coupon_id );
		$coupon->set_user_id( $this->user_id );

		$group = new CouponGroup( $group_id );
		$group->release_lock_for( $this );
	}

	public function record_popup( $data ) {
		list(
			'group_id' => $group_id,
			'timestamp' => $timestamp
		) = $data;

		$this->update_group_meta(
			array(
				'group_id'             => $group_id,
				'last_popup_timestamp' => $timestamp,
			)
		);
	}

	/** @todo make function which updates array meta with comparison function and update function */
	private function update_group_meta( $data ) {
		$previous_groups_meta = get_user_meta( $this->user_id, self::GROUPS_DATA_META_KEY );

		$group_index = array_search( $data['group_id'], array_column( $previous_groups_meta, 'group_id' ), true );
		if ( false === $group_index ) {
			add_user_meta( $this->user_id, self::GROUPS_DATA_META_KEY, $data );
		} else {
			$previous_meta = $previous_groups_meta[ $group_index ];
			$current_meta  = array_merge( $previous_meta, $data );
			update_user_meta( $this->user_id, self::GROUPS_DATA_META_KEY, $current_meta, $previous_meta );
		}
	}

	/**
	 * Registers the custom post type for Coupon.
	 */
	public static function register() {
		register_meta(
			'user',
			self::RETRIEVALS_META_KEY,
			array(
				'type'         => 'object',
				'description'  => 'All coupons retrieved by the user.',
				'single'       => false,
				'show_in_rest' => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'coupon_id'    => array( 'type' => 'integer' ),
							'retrieved_at' => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);

		register_meta(
			'user',
			self::GROUPS_DATA_META_KEY,
			array(
				'type'         => 'object',
				'description'  => 'Stats for all coupons groups per user.',
				'single'       => false,
				'show_in_rest' => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'group_id'                 => array( 'type' => 'integer' ),
							'last_popup_timestamp'     => array( 'type' => 'integer' ),
							'last_retrieval_timestamp' => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);
	}
}
