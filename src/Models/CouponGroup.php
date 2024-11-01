<?php
namespace UniqueCoupons\Models;

use UniqueCoupons\Utils;

class CouponGroup {
	const TAXONOMY_KEY    = 'unique_coupon_group';
	const TERM_QUERY_ARGS = array(
		'taxonomy'   => self::TAXONOMY_KEY,
		'orderby'    => 'term_id',
		'fields'     => 'ids',
		'hide_empty' => false,
	);

	/**
	 * Term id for the registered custom taxonomy.
	 *
	 * @var int
	 */
	public $group_id;

	public function __construct( int $group_id ) {
		$this->group_id = $group_id;
	}

	/** @todo maybe move to Popup service provider */
	public function echo_popup() {
		echo '<div class="unique-coupons-popup" data-group-id=' . esc_attr( $this->group_id ) . '>'
			. do_shortcode( wp_kses_post( $this->get_template() ) )
			. '</div>';
	}

	public function get_name() {
		return get_term( $this->group_id, self::TAXONOMY_KEY )->name;
	}
	public function get_description() {
		return get_term( $this->group_id, self::TAXONOMY_KEY )->description;
	}
	public function get_template(): string {
		return get_term_meta( $this->group_id, 'template', true );
	}
	public function get_is_active() {
		return (bool) get_term_meta( $this->group_id, 'is_active', true );
	}

	/** @return Coupon[] */
	public function get_coupons() {
		$coupon_ids = get_posts(
			array(
				'nopaging'    => true,
				'fields'      => 'ids',
				'post_type'   => Coupon::POST_TYPE_KEY,
				'post_status' => array( 'publish', 'trash' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery
				'tax_query'   => array(
					array(
						'taxonomy' => self::TAXONOMY_KEY,
						'terms'    => $this->group_id,
					),
				),
			)
		);

		return array_map(
			function( $coupon_id ) {
				return new Coupon( $coupon_id );
			},
			$coupon_ids
		);
	}

	public function has_distributable_coupons() {
		try {
			$this->get_distributable_coupon();
			return true;
		} catch ( \Exception $ex ) {
			return false;
		}
	}

	/** @throws \Exception */
	public function get_distributable_coupon(): Coupon {
		$coupon = Utils::array_find(
			$this->get_coupons(),
			function( $coupon ) {
				return $coupon->is_distributable();
			}
		);
		if ( ! $coupon ) {
			throw new \Exception( 'No distributable coupon found in group ' . $this->group_id );
		}
		return $coupon;
	}

	public function get_number_of_distributable_coupons() {
		$distributable_coupons = array_filter(
			$this->get_coupons(),
			function( $coupon ) {
				return $coupon->is_distributable();
			}
		);
		return count( $distributable_coupons );
	}

	public function lock_coupon_for( User $user, $lock_timeout_in_seconds = 5 * 60 ) {
		$locks                   = $this->get_locks();
		$locks[ $user->user_id ] = time() + $lock_timeout_in_seconds;
		$this->set_locks( $locks );
	}

	public function release_lock_for( User $user ) {
		$locks = $this->get_locks();
		unset( $locks[ $user->user_id ] );
		$this->set_locks( $locks );
	}

	public function has_unlocked_coupons() {
		return $this->get_number_of_distributable_coupons() > $this->get_number_of_locks();
	}

	public function get_number_of_locks() {
		return count( $this->get_locks() );
	}

	private function get_locks(): array {
		$locks = get_term_meta( $this->group_id, 'user_locks', true );
		$locks = is_array( $locks ) ? $locks : array();

		$now          = time();
		$active_locks = array_filter(
			$locks,
			function( $time ) use ( $now ) {
				return $now < $time;
			}
		);
		if ( count( $active_locks ) !== count( $locks ) ) {
			$this->set_locks( $active_locks );
		}
		return $active_locks;
	}

	private function set_locks( array $locks ) {
		update_term_meta( $this->group_id, 'user_locks', $locks );
	}

	/** @return CouponGroup[] */
	public static function get_active_groups() {
		$group_terms = get_terms(
			array_merge(
				self::TERM_QUERY_ARGS,
				array(
					// phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_key'   => 'is_active',
					// phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value' => true,
				)
			)
		);
		return array_map(
			function( $group_id ) {
				return new CouponGroup( $group_id );
			},
			$group_terms
		);
	}

	public static function exists( $group_id ) {
		$group_term = get_term( $group_id, self::TAXONOMY_KEY );
		return isset( $group_term ) && ! is_wp_error( $group_term );
	}

	/**
	 * @todo create DTO
	 * @return int
	 */
	public static function insert( $values ) {
		list(
			'name' => $name,
			'description' => $description,
			'template' => $template,
			'is_active' => $is_active
		) = $values;

		$term     = wp_insert_term(
			$name,
			self::TAXONOMY_KEY,
			array( 'description' => $description )
		);
		$group_id = $term['term_id'];
		add_term_meta( $group_id, 'template', $template, true );
		add_term_meta( $group_id, 'is_active', $is_active, true );

		return $group_id;
	}

	/**
	 * Registers the custom taxonomy for CouponGroups.
	 *
	 * @throws \Exception
	 */
	public static function register() {
		$taxonomy = register_taxonomy(
			self::TAXONOMY_KEY,
			array( Coupon::POST_TYPE_KEY ),
			array(
				'hierarchical'          => false,
				'public'                => false,
				'query_var'             => false,
				'rewrite'               => false,
				'capabilities'          => array(
					'manage_terms' => 'edit_posts',
					'edit_terms'   => 'edit_posts',
					'delete_terms' => 'edit_posts',
					'assign_terms' => 'edit_posts',
				),
				'labels'                => array(
					'name' => __( 'Coupon Groups', 'unique-coupons' ),
				),
				'show_in_rest'          => true,
				'rest_base'             => self::TAXONOMY_KEY,
				'rest_controller_class' => 'WP_REST_Terms_Controller',
			)
		);
		if ( is_wp_error( $taxonomy ) ) {
			throw new \Exception( 'Failed to register taxonomy ' . self::TAXONOMY_KEY );
		}

		$is_successful = register_term_meta(
			self::TAXONOMY_KEY,
			'template',
			array(
				'type'         => 'string',
				'description'  => 'The template to use for offering users coupons from this group.',
				'single'       => true,
				'show_in_rest' => true,
			)
		);
		if ( ! $is_successful ) {
			throw new \Exception( 'Faild to register term meta "template" for taxonomy ' . self::TAXONOMY_KEY );
		}

		$is_successful = register_term_meta(
			self::TAXONOMY_KEY,
			'is_active',
			array(
				'type'         => 'boolean',
				'description'  => 'Shows if coupons from this group get shown to users.',
				'single'       => true,
				'show_in_rest' => true,
			)
		);
		if ( ! $is_successful ) {
			throw new \Exception( 'Faild to register term meta "is_active" for taxonomy ' . self::TAXONOMY_KEY );
		}

		$is_successful = register_term_meta(
			self::TAXONOMY_KEY,
			'user_locks',
			array(
				'type'         => 'object',
				'description'  => 'Locks for coupon groups. An array of the form [user_id => lock_expiration].',
				'single'       => false,
				'show_in_rest' => false,
			)
		);
		if ( ! $is_successful ) {
			throw new \Exception( 'Faild to register term meta "user_locks" for taxonomy ' . self::TAXONOMY_KEY );
		}
	}
}
