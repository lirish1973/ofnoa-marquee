<?php
/**
 * Plugin Name:       Ofnoa Marquee — Logo & Text Ticker
 * Plugin URI:        https://github.com/lirish1973/ofnoa-marquee
 * Description:       Professional, fully customizable marquee / ticker for logos and text. Unlimited marquees, 4 directions, dual rows, edge fade, per-device settings, Gutenberg block, Elementor widget and shortcode.
 * Version:           1.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * Author:            Liraz
 * Author URI:        https://github.com/lirish1973
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ofnoa-marquee
 * Domain Path:       /languages
 * GitHub Plugin URI: lirish1973/ofnoa-marquee
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

define( 'OMQ_VERSION', '1.0.1' );
define( 'OMQ_FILE', __FILE__ );
define( 'OMQ_DIR', plugin_dir_path( __FILE__ ) );
define( 'OMQ_URL', plugin_dir_url( __FILE__ ) );
define( 'OMQ_BASENAME', plugin_basename( __FILE__ ) );
define( 'OMQ_SLUG', 'ofnoa-marquee' );
define( 'OMQ_CPT', 'omq_marquee' );
define( 'OMQ_GH_REPO', 'lirish1973/ofnoa-marquee' );

require_once OMQ_DIR . 'includes/class-omq-fields.php';
require_once OMQ_DIR . 'includes/class-omq-post-type.php';
require_once OMQ_DIR . 'includes/class-omq-metabox.php';
require_once OMQ_DIR . 'includes/class-omq-render.php';
require_once OMQ_DIR . 'includes/class-omq-shortcode.php';
require_once OMQ_DIR . 'includes/class-omq-block.php';
require_once OMQ_DIR . 'includes/class-omq-widget.php';
require_once OMQ_DIR . 'includes/class-omq-tools.php';
require_once OMQ_DIR . 'includes/class-omq-elementor.php';
require_once OMQ_DIR . 'includes/class-omq-updater.php';

/**
 * Main plugin bootstrapper.
 */
final class OMQ_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var OMQ_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return OMQ_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: wire everything up.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_front_assets' ) );
		add_filter( 'plugin_action_links_' . OMQ_BASENAME, array( $this, 'action_links' ) );

		OMQ_Post_Type::init();
		OMQ_Metabox::init();
		OMQ_Shortcode::init();
		OMQ_Block::init();
		OMQ_Widget::init();
		OMQ_Tools::init();
		OMQ_Elementor::init();
		OMQ_Updater::init();
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ofnoa-marquee', false, dirname( OMQ_BASENAME ) . '/languages' );
	}

	/**
	 * Register (not enqueue) front-end assets so they load only when a marquee renders.
	 */
	public function register_front_assets() {
		wp_register_style( 'omq-frontend', OMQ_URL . 'assets/css/omq-frontend.css', array(), OMQ_VERSION );
		wp_register_script( 'omq-frontend', OMQ_URL . 'assets/js/omq-frontend.js', array(), OMQ_VERSION, true );
	}

	/**
	 * Add a settings shortcut on the plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$custom = array(
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=' . OMQ_CPT ) ) . '">' . esc_html__( 'Marquees', 'ofnoa-marquee' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=' . OMQ_CPT . '&page=omq-tools' ) ) . '">' . esc_html__( 'Tools', 'ofnoa-marquee' ) . '</a>',
		);
		return array_merge( $custom, $links );
	}
}

/**
 * Shortcut accessor.
 *
 * @return OMQ_Plugin
 */
function omq() {
	return OMQ_Plugin::instance();
}
add_action( 'plugins_loaded', 'omq', 5 );

register_activation_hook(
	__FILE__,
	function () {
		OMQ_Post_Type::register_cpt();
		flush_rewrite_rules();
		if ( false === get_option( 'omq_options' ) ) {
			add_option(
				'omq_options',
				array(
					'delete_on_uninstall' => 0,
					'github_token'        => '',
					'disable_updates'     => 0,
				)
			);
		}
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
		delete_site_transient( 'omq_gh_release' );
	}
);
