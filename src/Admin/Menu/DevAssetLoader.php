<?php
namespace UniqueCoupons\Admin\Menu;

/**
 * Loads assets for the React admin frontend from the Webpack Dev Server.
 */
class DevAssetLoader extends AssetLoader {
	/**
	 * Loads assets from React app.
	 *
	 * @param string $assets_url URL to React assets (default is create-react-app default).
	 */
	public function __construct( $assets_url = 'http://localhost:3000' ) {
		parent::__construct( $assets_url );

		$this->scripts = array(
			array(
				'handle' => 'unique-coupons-dev-script-1',
				'url'    => $this->assets_url . '/static/js/bundle.js',
			),
			array(
				'handle' => 'unique-coupons-dev-script-2',
				'url'    => $this->assets_url . '/static/js/0.chunk.js',
			),
			array(
				'handle' => 'unique-coupons-dev-script-3',
				'url'    => $this->assets_url . '/static/js/main.chunk.js',
			),
		);
		$this->styles  = array();
	}
}
