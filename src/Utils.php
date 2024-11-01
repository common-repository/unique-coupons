<?php
namespace UniqueCoupons;

/**
 * General utility functions
 *
 * @todo split into more granular files, i.e. ArrayUtils, StringUtils, ...
 */
class Utils {
	/**
	 * Joins any number of paths with exactly one `/` between each.
	 * Does not remove leading or trailing slashes.
	 *
	 * @see https://stackoverflow.com/a/15575293/5312432
	 */
	public static function join_paths() {
		$paths = array_filter( func_get_args() );
		return preg_replace( '#/+#', '/', join( '/', $paths ) );
	}

	/**
	 * Tests if $haystack starts with $needle.
	 *
	 * @see https://stackoverflow.com/a/10473026/5312432
	 */
	public static function starts_with( $haystack, $needle ) {
		return substr_compare( $haystack, $needle, 0, strlen( $needle ) ) === 0;
	}

	/**
	 * Tests if $haystack ends with $needle.
	 *
	 * @see https://stackoverflow.com/a/10473026/5312432
	 */
	public static function ends_with( $haystack, $needle ) {
		return substr_compare( $haystack, $needle, -strlen( $needle ) ) === 0;
	}

	/**
	 * Gets JSON from remote URL with `wp_remote_get`.
	 *
	 * @throws \Exception
	 */
	public static function get_json( $url, $args = array() ) {
		$response = wp_remote_get( $url, $args );
		if ( $response instanceof \WP_Error ) {
			throw new \Exception( 'Failed to get JSON file.' );
		}

		$json = json_decode( $response['body'], true );
		if ( is_null( $json ) ) {
			throw new \Exception( 'Failed to parse JSON file.' );
		}

		return $json;
	}

	/**
	 * Checks if any element in the array fulfills the predicate.
	 *
	 * @see https://stackoverflow.com/a/39877269/5312432
	 */
	public static function array_any( array $array, callable $fn ) {
		foreach ( $array as $value ) {
			if ( $fn( $value ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if all elements in the array fulfill the predicate.
	 *
	 * @see https://stackoverflow.com/a/39877269/5312432
	 */
	public static function array_every( array $array, callable $fn ) {
		foreach ( $array as $value ) {
			if ( ! $fn( $value ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns the first element which returns a truthy value for `$fn`.
	 * Returns `$default`, when the element is not found.
	 */
	public static function array_find( array $array, callable $fn, $default = null ) {
		foreach ( $array as $element ) {
			if ( $fn( $element ) ) {
				return $element;
			}
		}
		return $default;
	}
}
