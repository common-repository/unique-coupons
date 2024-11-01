<?php
namespace UniqueCoupons\Admin\Menu;

/**
 * Adds a new menu page rendered with React.
 */
class Menu {
	/**
	 * ID of the HTML element React should render to.
	 *
	 * @var string
	 */
	protected $root_element_id;

	/**
	 * Loader for assets from React app.
	 *
	 * @var AssetLoader
	 */
	protected $asset_loader;

	/**
	 * Adds a menu page and loads assets from React app.
	 *
	 * @param string      $root_element_id ID of the HTML element React should render to.
	 * @param AssetLoader $asset_loader Loader for assets from React app.
	 */
	public function __construct( $root_element_id, $asset_loader ) {
		$this->root_element_id = $root_element_id;
		$this->asset_loader    = $asset_loader;
	}

	/**
	 * Add submenu page for coupons.
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'Coupons', 'unique-coupons' ),
			__( 'Coupons', 'unique-coupons' ),
			'manage_options',
			'unique-coupons',
			array( $this, 'render_admin' )
		);
	}

	/**
	 * Render plugin admin page.
	 */
	public function render_admin() {
		$this->asset_loader->enqueue_assets();
		echo '<div id="' . esc_attr( $this->root_element_id ) . '"></div>';
		echo '<script>document.getElementById("' . esc_js( $this->root_element_id ) . '").attachShadow({ mode: "open" })</script>';
	}
}
