<?php
/**
 * Shortcode + template helper.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Shortcode
 */
class OMQ_Shortcode {

	/**
	 * Hook.
	 */
	public static function init() {
		add_shortcode( 'ofnoa_marquee', array( __CLASS__, 'render' ) );
		add_shortcode( 'omq', array( __CLASS__, 'render' ) );
	}

	/**
	 * Shortcode handler. Any field key may be passed as an attribute override.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();
		$atts = array_change_key_case( $atts, CASE_LOWER );

		$id = 0;
		if ( isset( $atts['id'] ) ) {
			$id = absint( $atts['id'] );
			unset( $atts['id'] );
		}

		if ( ! $id ) {
			return '';
		}

		$allowed   = OMQ_Fields::flat();
		$overrides = array();
		foreach ( $atts as $key => $value ) {
			$key = str_replace( '-', '_', $key );
			if ( isset( $allowed[ $key ] ) ) {
				$overrides[ $key ] = $value;
			}
		}

		return OMQ_Render::render_post( $id, $overrides );
	}
}

if ( ! function_exists( 'ofnoa_marquee' ) ) {
	/**
	 * Template tag: echo a marquee.
	 *
	 * @param int   $id        Marquee ID.
	 * @param array $overrides Optional overrides.
	 */
	function ofnoa_marquee( $id, $overrides = array() ) {
		echo OMQ_Render::render_post( $id, $overrides ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'ofnoa_get_marquee' ) ) {
	/**
	 * Template tag: return a marquee.
	 *
	 * @param int   $id        Marquee ID.
	 * @param array $overrides Optional overrides.
	 * @return string
	 */
	function ofnoa_get_marquee( $id, $overrides = array() ) {
		return OMQ_Render::render_post( $id, $overrides );
	}
}
