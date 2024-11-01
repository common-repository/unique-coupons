<?php
/**
 * Plugin Name:     Unique Coupons
 * Plugin URI:      https://wordpress.org/plugins/unique-coupons/
 * Description:     Distribute unique coupons to your users
 * Author:          Josef Wittmann <josef.wittmann@tutanota.com>
 * Author URI:      https://josefwittmann.dev/
 * Text Domain:     unique-coupons
 * Domain Path:     /languages
 * Version:         0.1.3
 */

require __DIR__ . '/vendor/autoload.php';

function unique_coupons_load_plugin() {
	$version         = '0.1.3';
	$plugin_root_dir = plugin_dir_path( __FILE__ );
	$plugin_root_url = plugin_dir_url( __FILE__ );
	$loader          = new UniqueCoupons\Loader( $version, $plugin_root_dir, $plugin_root_url );
	$loader->run();
}

unique_coupons_load_plugin();
