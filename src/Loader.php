<?php
namespace UniqueCoupons;

/**
 * Bootstraps the plugin.
 */
class Loader {
	/** @var string */
	private $plugin_version;
	/** @var string */
	private $plugin_root_dir;
	/** @var string */
	private $plugin_root_url;
	/** @var string */
	private $plugin_name;

	/**
	 * Sets up plugin constants.
	 */
	public function __construct( $plugin_version, $plugin_root_dir, $plugin_root_url ) {
		$this->plugin_version  = $plugin_version;
		$this->plugin_root_dir = $plugin_root_dir;
		$this->plugin_root_url = $plugin_root_url;
		$this->plugin_name     = 'unique-coupons';
	}

	/**
	 * Loads everything neccessary for the plugin.
	 */
	public function run() {
		add_action( 'init', array( $this, 'register_models' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		if ( is_admin() ) {
			$this->load_admin();
		} else {
			$this->load_frontend();
		}
	}

	public function register_models() {
		Models\Coupon::register();
		Models\CouponGroup::register();
		Models\User::register();
	}

	public function register_rest_routes() {
		$version   = 1;
		$namespace = $this->plugin_name . '/v' . $version;

		( new Routes\CouponsAdminRoute( $namespace ) )->register_routes();
		( new Routes\RetrieveCouponRoute( $namespace ) )->register_routes();
		( new Routes\OptionsRoute( $namespace ) )->register_routes();
		( new Routes\PopupActionsRoute( $namespace ) )->register_routes();
	}

	private function load_admin() {
		$react_assets_url = $this->plugin_root_url . 'src/Admin/view/build';
		$root_element_id  = 'unique-coupons-root';

		$asset_loader = new Admin\Menu\LiveAssetLoader( $react_assets_url );
		$menu         = new Admin\Menu\Menu( $root_element_id, $asset_loader );

		add_action( 'admin_menu', array( $menu, 'add_menu_page' ) );
	}

	private function load_frontend() {
		$this->init_controllers();
		$this->register_scripts();
	}

	private function init_controllers() {
		add_filter(
			'query_vars',
			function( $query_vars ) {
				$query_vars[] = 'unique-coupons-preview';
				return $query_vars;
			}
		);
		add_action(
			'wp',
			function() {
				$preview_group_id = (int) get_query_var( 'unique-coupons-preview' );
				$popup_controller = $preview_group_id
					? new Controllers\PopupPreviewController( $preview_group_id )
					: new Controllers\PopupController();
				$popup_controller->init_popup();
			}
		);
	}

	/** @todo Use dedicated loader? */
	private function register_scripts() {
		$assets_path = 'src/assets/';
		$assets_url  = $this->plugin_root_url . $assets_path;
		$assets_dir  = $this->plugin_root_dir . $assets_path;

		add_action(
			'init',
			function() use ( $assets_url, $assets_dir ) {
				wp_register_style( 'jquery-modal', $assets_url . 'jquery.modal.min.css', array(), '0.9.2' );
				wp_register_script( 'jquery-modal', $assets_url . 'jquery.modal.min.js', array( 'jquery' ), '0.9.2', true );

				wp_register_style(
					'unique-coupons-popup',
					$assets_url . 'popup.css',
					array( 'jquery-modal' ),
					filemtime( $assets_dir . 'popup.css' )
				);
				$modal_z_index = Options::get_modal_z_index();
				$inline_css    = ".unique-coupons-jquery-modal.blocker { z-index: ${modal_z_index}; }";
				wp_add_inline_style( 'unique-coupons-popup', $inline_css );
				wp_register_script(
					'unique-coupons-popup',
					$assets_url . 'popup.js',
					array( 'jquery-modal' ),
					filemtime( $assets_dir . 'popup.js' ),
					true
				);
				wp_localize_script(
					'unique-coupons-popup',
					'uniqueCouponsPopup',
					array(
						'timeoutInSeconds' => Options::get_seconds_from_page_load_to_popup(),
						'api'              => array(
							'nonce'          => wp_create_nonce( 'wp_rest' ),
							'retrieveCoupon' => esc_url( rest_url( 'unique-coupons/v1/retrieve-coupon' ) ),
							'onPopupOpen'    => esc_url( rest_url( 'unique-coupons/v1/on-popup-open' ) ),
							'onPopupClose'   => esc_url( rest_url( 'unique-coupons/v1/on-popup-close' ) ),
						),
					)
				);
			}
		);
	}
}
