<?php
namespace UniqueCoupons\Models;

use UniqueCoupons\Options;

class Coupon {
	const POST_TYPE_KEY = 'unique_coupon';

	/**
	 * The post id for the coupon custom post type.
	 *
	 * @var int
	 */
	public $coupon_id;

	public function __construct( int $coupon_id ) {
		$this->coupon_id = $coupon_id;
	}

	public function is_distributable() {
		return $this->is_active()
			&& $this->is_unused()
			&& $this->is_valid_for_distribution();
	}

	public function is_active() {
		return 'publish' === $this->get_post_status();
	}

	public function is_unused() {
		return empty( $this->get_user_id() );
	}

	public function is_valid_for_distribution() {
		$distribution_slack_time = time() + Options::get_seconds_valid_after_distribution();
		return $this->is_valid_at( $distribution_slack_time );
	}
	public function is_valid() {
		$now = time();
		return $this->is_valid_at( $now );
	}
	private function is_valid_at( int $time ) {
		return $time <= $this->get_expires_at();
	}

	/** @todo unit test */
	public function get_status() {
		if ( ! $this->is_unused() ) {
			return 'used';
		}
		if ( ! $this->is_valid_for_distribution() ) {
			return 'expired';
		}
		if ( ! $this->is_active() ) {
			return 'inactive';
		}
		return 'active';
	}

	public function get_value() {
		return get_the_title( $this->coupon_id );
	}
	public function get_group_id(): int {
		$terms = wp_get_post_terms(
			$this->coupon_id,
			CouponGroup::TAXONOMY_KEY,
			CouponGroup::TERM_QUERY_ARGS
		);
		return $terms[0];
	}
	public function get_user_id() {
		return (int) get_post_meta( $this->coupon_id, 'user_id', true );
	}
	public function get_expires_at() {
		return get_post_meta( $this->coupon_id, 'expires_at', true );
	}
	public function get_post_status() {
		return get_post_status( $this->coupon_id );
	}

	public function set_user_id( $user_id ) {
		$success = (bool) update_post_meta( $this->coupon_id, 'user_id', $user_id );
		if ( ! $success ) {
			throw new \Exception( "Failed to record that user $user_id retrieved coupon $this->coupon_id." );
		}
	}

	/**
	 * Inserts a new Coupon into the database.
	 *
	 * @param array $args [
	 *   'value' => string,
	 *   'group_id' => int,
	 *   'expires_at => Unix timestamp (seconds),
	 *   'status' => string
	 * ].
	 * @throws \Exception From `wp_insert_post`.
	 * @return int The post ID.
	 */
	public static function insert( $args ) {
		$defaults = array(
			'status' => 'publish',
		);

		list(
			'value'      => $value,
			'group_id'   => $group_id,
			'expires_at' => $expires_at,
			'status'     => $status,
		) = wp_parse_args( $args, $defaults );

		$postarr         = array(
			'post_title'  => $value,
			'post_status' => $status,
			'post_type'   => self::POST_TYPE_KEY,
			'meta_input'  => array( 'expires_at' => $expires_at ),
		);
		$do_return_error = true;

		$post_id_or_error = wp_insert_post( $postarr, $do_return_error );

		if ( is_wp_error( $post_id_or_error ) ) {
			$error = $post_id_or_error;
			throw new \Exception( $error->get_error_message() );
		}

		$post_id = $post_id_or_error;

		// We can't use 'tax_input' when user does not have capabilities to work with taxonomies.
		$term_ids_or_error = wp_set_object_terms(
			$post_id,
			$group_id,
			CouponGroup::TAXONOMY_KEY
		);

		if ( is_wp_error( $term_ids_or_error ) ) {
			self::delete( $post_id );
			$error = $term_ids_or_error;
			throw new \Exception( $error->get_error_message() );
		}

		return $post_id;
	}

	/**
	 * Deletes the coupon and skips trash.
	 */
	public static function delete( $id ) {
		$skip_trash = true;
		wp_delete_post( $id, $skip_trash );
	}

	/**
	 * Deletes the coupon and skips trash.
	 */
	public static function deactivate( $id ) {
		wp_trash_post( $id );
	}

	/**
	 * Deletes the coupon and skips trash.
	 */
	public static function activate( $id ) {
		wp_publish_post( $id );
	}

	/**
	 * Registers the custom post type for Coupon.
	 *
	 * @throws \Exception
	 */
	public static function register() {
		$post_type = register_post_type(
			self::POST_TYPE_KEY,
			array(
				'label'        => 'Coupons',
				'public'       => false,
				'hierarchical' => false,
				'supports'     => array( 'title', 'editor', 'custom-fields', 'page-attributes' ),
				'rewrite'      => false,
				'show_in_rest' => false,
			)
		);
		if ( is_wp_error( $post_type ) ) {
			throw new \Exception( 'Failed to register post type ' . self::POST_TYPE_KEY );
		}

		$is_successful = register_post_meta(
			self::POST_TYPE_KEY,
			'expires_at',
			array(
				'type'        => 'integer',
				'description' => 'Last moment of coupon validity (as Unix timestamp in seconds)',
				'single'      => true,
			)
		);
		if ( ! $is_successful ) {
			throw new \Exception( 'Failed to register "expires_at" meta for post type ' . self::POST_TYPE_KEY );
		}

		$is_successful = register_post_meta(
			self::POST_TYPE_KEY,
			'user_id',
			array(
				'type'        => 'integer',
				'description' => 'ID of user, who received the coupon.',
				'single'      => true,
			)
		);
		if ( ! $is_successful ) {
			throw new \Exception( 'Failed to register "user_id" meta for post type ' . self::POST_TYPE_KEY );
		}
	}
}
