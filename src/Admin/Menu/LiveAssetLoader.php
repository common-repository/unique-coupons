<?php
namespace UniqueCoupons\Admin\Menu;

/**
 * Loads assets for the React admin frontend from the `build` folder (via `asset-manifest.json`).
 */
class LiveAssetLoader extends AssetLoader {
	/**
	 * Loads assets React app from `build` folder (via `asset-manifest.json`).
	 *
	 * @param string $assets_url URL to React assets.
	 */
	public function __construct( $assets_url ) {
		parent::__construct( $assets_url );

		$assets        = $this->get_assets();
		$this->scripts = $this->get_assets_ending_with( $assets, '.js' );
		$this->styles  = $this->get_assets_ending_with( $assets, '.css' );
	}

	/**
	 * Gets assets from `build` folder and adds URL to them.
	 *
	 * @todo Asset DTO/Class
	 * @returns array('handle' => string, 'url' => string)
	 */
	protected function get_assets() {
		$entrypoints = $this->get_entrypoints();
		$assets      = array_map( array( $this, 'get_asset_from_entrypoint' ), $entrypoints );
		return $assets;
	}

	/**
	 * Gets entrypoints from React build folder.
	 */
	protected function get_entrypoints() {
		$asset_manifest_url = $this->assets_url . '/asset-manifest.json';
		$asset_manifest     = \UniqueCoupons\Utils::get_json( $asset_manifest_url );
		return $asset_manifest['entrypoints'];
	}

	/**
	 * Determines `handle` and `url` for an asset entrypoint.
	 */
	protected function get_asset_from_entrypoint( $entrypoint ) {
		return array(
			'handle' => $entrypoint,
			'url'    => $this->assets_url . '/' . $entrypoint,
		);
	}

	/**
	 * Filters all assets whose `url` value ends with `$tail`.
	 */
	protected function get_assets_ending_with( $assets, $tail ) {
		return array_filter(
			$assets,
			function( $asset ) use ( $tail ) {
				return \UniqueCoupons\Utils::ends_with( $asset['url'], $tail );
			}
		);
	}
}
