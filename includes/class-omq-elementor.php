<?php
/**
 * Elementor integration.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Elementor
 */
class OMQ_Elementor {

	/**
	 * Hook (only if Elementor is active).
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register' ) );
		add_action( 'elementor/widgets/widgets_registered', array( __CLASS__, 'register_legacy' ) );
	}

	/**
	 * Elementor 3.5+.
	 *
	 * @param object $widgets_manager Widgets manager.
	 */
	public static function register( $widgets_manager ) {
		if ( ! self::load_widget() ) {
			return;
		}
		$widgets_manager->register( new OMQ_Elementor_Widget() );
	}

	/**
	 * Legacy Elementor.
	 *
	 * @param object $widgets_manager Widgets manager.
	 */
	public static function register_legacy( $widgets_manager ) {
		if ( method_exists( $widgets_manager, 'register' ) ) {
			return; // Modern hook already handled it.
		}
		if ( ! self::load_widget() || ! method_exists( $widgets_manager, 'register_widget_type' ) ) {
			return;
		}
		$widgets_manager->register_widget_type( new OMQ_Elementor_Widget() );
	}

	/**
	 * Define the widget class once Elementor is loaded.
	 *
	 * @return bool
	 */
	public static function load_widget() {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
			return false;
		}
		if ( class_exists( 'OMQ_Elementor_Widget' ) ) {
			return true;
		}
		require_once OMQ_DIR . 'includes/class-omq-elementor-widget.php';
		return class_exists( 'OMQ_Elementor_Widget' );
	}

	/**
	 * Marquee options for select controls.
	 *
	 * @return array
	 */
	public static function options() {
		$out = array( 0 => __( '— Select a marquee —', 'ofnoa-marquee' ) );

		foreach ( OMQ_Post_Type::get_marquees() as $marquee ) {
			$out[ $marquee->ID ] = OMQ_Post_Type::label( $marquee );
		}

		return $out;
	}
}
