<?php

namespace UniqueCoupons;

class Options {
	const OPTION_NAME = 'unique_coupons_options';
	/** @var int[] */
	public static $default_options;

	public static function init_static() {
		self::$default_options = array(
			'seconds_between_any_popup'        => strtotime( '1 day', 0 ),
			'seconds_between_same_popup'       => strtotime( '7 days', 0 ),
			'seconds_between_any_retrieval'    => strtotime( '7 days', 0 ),
			'seconds_between_same_retrieval'   => strtotime( '30 days', 0 ),
			'seconds_valid_after_distribution' => strtotime( '3 days', 0 ),
			'seconds_from_page_load_to_popup'  => strtotime( '10 seconds', 0 ),
			'modal_z_index'                    => '9999',
		);
	}

	public static function get_options() {
		$options = get_option( self::OPTION_NAME, array() );
		$result  = array();
		foreach ( self::$default_options as $key => $default_value ) {
			$result[ $key ] = $options[ $key ] ?? $default_value;
		}
		return $result;
	}

	public static function get_option( $key ) {
		$options = self::get_options();
		if ( ! array_key_exists( $key, $options ) ) {
			throw new \Exception( "Unknown option $key" );
		}
		return $options[ $key ];
	}

	public static function get_seconds_between_any_popup() {
		return self::get_option( 'seconds_between_any_popup' );
	}
	public static function get_seconds_between_same_popup() {
		return self::get_option( 'seconds_between_same_popup' );
	}
	public static function get_seconds_between_any_retrieval() {
		return self::get_option( 'seconds_between_any_retrieval' );
	}
	public static function get_seconds_between_same_retrieval() {
		return self::get_option( 'seconds_between_same_retrieval' );
	}
	public static function get_seconds_valid_after_distribution() {
		return self::get_option( 'seconds_valid_after_distribution' );
	}
	public static function get_seconds_from_page_load_to_popup() {
		return self::get_option( 'seconds_from_page_load_to_popup' );
	}
	public static function get_modal_z_index() {
		return self::get_option( 'modal_z_index' );
	}

	public static function set_options( $options ) {
		return update_option( self::OPTION_NAME, $options );
	}

	public static function set_option( $key, $option ) {
		$current_options = self::get_options();
		$new_options     = array_merge( $current_options, array( $key => $option ) );
		return self::set_options( $new_options );
	}
}

Options::init_static();
