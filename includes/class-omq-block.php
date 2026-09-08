<?php
/**
 * Gutenberg block (server rendered).
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Block
 */
class OMQ_Block {

	/**
	 * Hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_assets' ) );
	}

	/**
	 * Register the block type.
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'ofnoa/marquee',
			array(
				'api_version'     => 2,
				'title'           => __( 'Ofnoa Marquee', 'ofnoa-marquee' ),
				'category'        => 'widgets',
				'icon'            => 'controls-repeat',
				'editor_script'   => 'omq-block',
				'editor_style'    => 'omq-frontend',
				'style'           => 'omq-frontend',
				'attributes'      => array(
					'marqueeId' => array(
						'type'    => 'number',
						'default' => 0,
					),
					'speed'     => array(
						'type'    => 'number',
						'default' => 0,
					),
					'direction' => array(
						'type'    => 'string',
						'default' => '',
					),
					'align'     => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'supports'        => array(
					'align'  => array( 'wide', 'full' ),
					'anchor' => true,
				),
				'render_callback' => array( __CLASS__, 'render' ),
			)
		);
	}

	/**
	 * Server-side render.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$id = isset( $attributes['marqueeId'] ) ? absint( $attributes['marqueeId'] ) : 0;
		if ( ! $id ) {
			return '';
		}

		$overrides = array();
		if ( ! empty( $attributes['speed'] ) ) {
			$overrides['speed'] = (int) $attributes['speed'];
		}
		if ( ! empty( $attributes['direction'] ) ) {
			$overrides['direction'] = sanitize_text_field( $attributes['direction'] );
		}

		$html = OMQ_Render::render_post( $id, $overrides );

		$class = 'wp-block-ofnoa-marquee';
		if ( ! empty( $attributes['align'] ) ) {
			$class .= ' align' . sanitize_html_class( $attributes['align'] );
		}

		return '<div class="' . esc_attr( $class ) . '">' . $html . '</div>';
	}

	/**
	 * Editor script + the marquee list.
	 */
	public static function editor_assets() {
		wp_enqueue_script(
			'omq-block',
			OMQ_URL . 'assets/js/omq-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ),
			OMQ_VERSION,
			true
		);

		$marquees = get_posts(
			array(
				'post_type'      => OMQ_CPT,
				'posts_per_page' => 200,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$options = array(
			array(
				'label' => __( '— Select a marquee —', 'ofnoa-marquee' ),
				'value' => 0,
			),
		);
		foreach ( $marquees as $marquee ) {
			$options[] = array(
				'label' => $marquee->post_title ? $marquee->post_title : sprintf( '#%d', $marquee->ID ),
				'value' => $marquee->ID,
			);
		}

		wp_localize_script(
			'omq-block',
			'omqBlock',
			array(
				'options' => $options,
				'newUrl'  => admin_url( 'post-new.php?post_type=' . OMQ_CPT ),
			)
		);
	}
}
