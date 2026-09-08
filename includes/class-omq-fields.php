<?php
/**
 * Field schema: single source of truth for defaults, admin UI and sanitization.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Fields
 */
class OMQ_Fields {

	/**
	 * Cached flat schema.
	 *
	 * @var array|null
	 */
	protected static $flat = null;

	/**
	 * Full grouped schema.
	 *
	 * @return array
	 */
	public static function schema() {
		$schema = array(

			'content'    => array(
				'label'  => __( 'Content', 'ofnoa-marquee' ),
				'icon'   => 'dashicons-list-view',
				'fields' => array(
					'display_mode'    => array(
						'label'   => __( 'Marquee type', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'logos',
						'options' => array(
							'logos' => __( 'Logos / images', 'ofnoa-marquee' ),
							'text'  => __( 'Text ticker', 'ofnoa-marquee' ),
							'both'  => __( 'Mixed (image + text)', 'ofnoa-marquee' ),
						),
						'desc'    => __( 'Controls how each item is rendered.', 'ofnoa-marquee' ),
					),
					'content_source'  => array(
						'label'   => __( 'Content source', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'manual',
						'options' => array(
							'manual' => __( 'Manual items', 'ofnoa-marquee' ),
							'posts'  => __( 'Dynamic — from posts', 'ofnoa-marquee' ),
						),
					),
					'source_post_type' => array(
						'label'      => __( 'Post type', 'ofnoa-marquee' ),
						'type'       => 'select',
						'default'    => 'post',
						'options_cb' => array( __CLASS__, 'post_type_options' ),
						'condition'  => array( 'content_source' => array( 'posts' ) ),
					),
					'source_count'    => array(
						'label'     => __( 'How many items', 'ofnoa-marquee' ),
						'type'      => 'number',
						'default'   => 10,
						'min'       => 1,
						'max'       => 100,
						'condition' => array( 'content_source' => array( 'posts' ) ),
					),
					'source_orderby'  => array(
						'label'     => __( 'Order by', 'ofnoa-marquee' ),
						'type'      => 'select',
						'default'   => 'date',
						'options'   => array(
							'date'     => __( 'Newest first', 'ofnoa-marquee' ),
							'title'    => __( 'Title', 'ofnoa-marquee' ),
							'rand'     => __( 'Random', 'ofnoa-marquee' ),
							'modified' => __( 'Recently modified', 'ofnoa-marquee' ),
						),
						'condition' => array( 'content_source' => array( 'posts' ) ),
					),
					'source_link'     => array(
						'label'     => __( 'Link items to the post', 'ofnoa-marquee' ),
						'type'      => 'toggle',
						'default'   => 1,
						'condition' => array( 'content_source' => array( 'posts' ) ),
					),
				),
			),

			'layout'     => array(
				'label'  => __( 'Layout', 'ofnoa-marquee' ),
				'icon'   => 'dashicons-layout',
				'fields' => array(
					'direction'        => array(
						'label'   => __( 'Direction', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'left',
						'options' => array(
							'left'  => __( 'Right to left  ←', 'ofnoa-marquee' ),
							'right' => __( 'Left to right  →', 'ofnoa-marquee' ),
							'up'    => __( 'Bottom to top  ↑', 'ofnoa-marquee' ),
							'down'  => __( 'Top to bottom  ↓', 'ofnoa-marquee' ),
						),
					),
					'rows'             => array(
						'label'   => __( 'Rows', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => '1',
						'options' => array(
							'1' => __( 'Single ticker', 'ofnoa-marquee' ),
							'2' => __( 'Double ticker (2 rows)', 'ofnoa-marquee' ),
							'3' => __( 'Triple ticker (3 rows)', 'ofnoa-marquee' ),
						),
					),
					'rows_mode'        => array(
						'label'     => __( 'Extra rows direction', 'ofnoa-marquee' ),
						'type'      => 'select',
						'default'   => 'opposite',
						'options'   => array(
							'opposite' => __( 'Opposite direction (ping-pong)', 'ofnoa-marquee' ),
							'same'     => __( 'Same direction', 'ofnoa-marquee' ),
						),
						'condition' => array( 'rows' => array( '2', '3' ) ),
					),
					'rows_speed'       => array(
						'label'     => __( 'Extra rows speed', 'ofnoa-marquee' ),
						'type'      => 'range',
						'default'   => 100,
						'min'       => 10,
						'max'       => 300,
						'step'      => 5,
						'unit'      => '%',
						'condition' => array( 'rows' => array( '2', '3' ) ),
					),
					'rows_offset'      => array(
						'label'     => __( 'Extra rows start offset', 'ofnoa-marquee' ),
						'type'      => 'range',
						'default'   => 25,
						'min'       => 0,
						'max'       => 100,
						'step'      => 1,
						'unit'      => '%',
						'desc'      => __( 'Shifts the animation start so rows never line up.', 'ofnoa-marquee' ),
						'condition' => array( 'rows' => array( '2', '3' ) ),
					),
					'rows_gap'         => array(
						'label'     => __( 'Gap between rows', 'ofnoa-marquee' ),
						'type'      => 'number',
						'default'   => 20,
						'min'       => 0,
						'max'       => 200,
						'unit'      => 'px',
						'condition' => array( 'rows' => array( '2', '3' ) ),
					),
					'gap'              => array(
						'label'      => __( 'Gap between items', 'ofnoa-marquee' ),
						'type'       => 'number',
						'default'    => 40,
						'min'        => 0,
						'max'        => 300,
						'unit'       => 'px',
						'responsive' => true,
					),
					'align'            => array(
						'label'   => __( 'Items alignment', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'center',
						'options' => array(
							'center'     => __( 'Center', 'ofnoa-marquee' ),
							'flex-start' => __( 'Start', 'ofnoa-marquee' ),
							'flex-end'   => __( 'End', 'ofnoa-marquee' ),
							'stretch'    => __( 'Stretch', 'ofnoa-marquee' ),
						),
					),
					'width_mode'       => array(
						'label'   => __( 'Width', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'full',
						'options' => array(
							'full'  => __( 'Full width of container', 'ofnoa-marquee' ),
							'boxed' => __( 'Boxed (max width)', 'ofnoa-marquee' ),
						),
					),
					'max_width'        => array(
						'label'     => __( 'Max width', 'ofnoa-marquee' ),
						'type'      => 'number',
						'default'   => 1200,
						'min'       => 100,
						'max'       => 3000,
						'unit'      => 'px',
						'condition' => array( 'width_mode' => array( 'boxed' ) ),
					),
					'vertical_height'  => array(
						'label'     => __( 'Height (vertical marquee)', 'ofnoa-marquee' ),
						'type'      => 'number',
						'default'   => 320,
						'min'       => 50,
						'max'       => 2000,
						'unit'      => 'px',
						'condition' => array( 'direction' => array( 'up', 'down' ) ),
					),
					'padding_y'        => array(
						'label'   => __( 'Vertical padding', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 16,
						'min'     => 0,
						'max'     => 200,
						'unit'    => 'px',
					),
					'padding_x'        => array(
						'label'   => __( 'Horizontal padding', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 200,
						'unit'    => 'px',
					),
					'margin_y'         => array(
						'label'   => __( 'Outer vertical margin', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 200,
						'unit'    => 'px',
					),
				),
			),

			'style'      => array(
				'label'  => __( 'Container style', 'ofnoa-marquee' ),
				'icon'   => 'dashicons-art',
				'fields' => array(
					'bg_type'       => array(
						'label'   => __( 'Background', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'none',
						'options' => array(
							'none'     => __( 'Transparent', 'ofnoa-marquee' ),
							'solid'    => __( 'Solid color', 'ofnoa-marquee' ),
							'gradient' => __( 'Gradient', 'ofnoa-marquee' ),
						),
					),
					'bg_color'      => array(
						'label'     => __( 'Background color', 'ofnoa-marquee' ),
						'type'      => 'color',
						'default'   => '#ffffff',
						'condition' => array( 'bg_type' => array( 'solid' ) ),
					),
					'grad_from'     => array(
						'label'     => __( 'Gradient from', 'ofnoa-marquee' ),
						'type'      => 'color',
						'default'   => '#4f46e5',
						'condition' => array( 'bg_type' => array( 'gradient' ) ),
					),
					'grad_to'       => array(
						'label'     => __( 'Gradient to', 'ofnoa-marquee' ),
						'type'      => 'color',
						'default'   => '#06b6d4',
						'condition' => array( 'bg_type' => array( 'gradient' ) ),
					),
					'grad_angle'    => array(
						'label'     => __( 'Gradient angle', 'ofnoa-marquee' ),
						'type'      => 'range',
						'default'   => 90,
						'min'       => 0,
						'max'       => 360,
						'step'      => 5,
						'unit'      => 'deg',
						'condition' => array( 'bg_type' => array( 'gradient' ) ),
					),
					'border_width'  => array(
						'label'   => __( 'Border width', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 20,
						'unit'    => 'px',
					),
					'border_style'  => array(
						'label'   => __( 'Border style', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'solid',
						'options' => array(
							'solid'  => __( 'Solid', 'ofnoa-marquee' ),
							'dashed' => __( 'Dashed', 'ofnoa-marquee' ),
							'dotted' => __( 'Dotted', 'ofnoa-marquee' ),
							'double' => __( 'Double', 'ofnoa-marquee' ),
						),
					),
					'border_color'  => array(
						'label'   => __( 'Border color', 'ofnoa-marquee' ),
						'type'    => 'color',
						'default' => '#e5e7eb',
					),
					'radius'        => array(
						'label'   => __( 'Border radius', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 200,
						'unit'    => 'px',
					),
					'shadow'        => array(
						'label'   => __( 'Box shadow', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'none',
						'options' => array(
							'none'   => __( 'None', 'ofnoa-marquee' ),
							'sm'     => __( 'Soft', 'ofnoa-marquee' ),
							'md'     => __( 'Medium', 'ofnoa-marquee' ),
							'lg'     => __( 'Strong', 'ofnoa-marquee' ),
							'custom' => __( 'Custom', 'ofnoa-marquee' ),
						),
					),
					'shadow_custom' => array(
						'label'     => __( 'Custom shadow', 'ofnoa-marquee' ),
						'type'      => 'text',
						'default'   => '0 10px 30px rgba(0,0,0,.12)',
						'condition' => array( 'shadow' => array( 'custom' ) ),
					),
					'fade_edges'    => array(
						'label'   => __( 'Fade edges', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 1,
						'desc'    => __( 'Softly fades the marquee at both ends (uses CSS mask — works on any background).', 'ofnoa-marquee' ),
					),
					'fade_width'    => array(
						'label'     => __( 'Fade size', 'ofnoa-marquee' ),
						'type'      => 'range',
						'default'   => 80,
						'min'       => 5,
						'max'       => 300,
						'unit'      => 'px',
						'condition' => array( 'fade_edges' => array( 1 ) ),
					),
				),
			),

			'items'      => array(
				'label'  => __( 'Items style', 'ofnoa-marquee' ),
				'icon'   => 'dashicons-images-alt2',
				'fields' => array(
					'logo_height'     => array(
						'label'      => __( 'Logo height', 'ofnoa-marquee' ),
						'type'       => 'number',
						'default'    => 60,
						'min'        => 10,
						'max'        => 600,
						'unit'       => 'px',
						'responsive' => true,
					),
					'logo_max_width'  => array(
						'label'   => __( 'Logo max width (0 = auto)', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 1000,
						'unit'    => 'px',
					),
					'logo_fit'        => array(
						'label'   => __( 'Image fit', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'contain',
						'options' => array(
							'contain' => __( 'Contain', 'ofnoa-marquee' ),
							'cover'   => __( 'Cover', 'ofnoa-marquee' ),
							'fill'    => __( 'Fill', 'ofnoa-marquee' ),
							'none'    => __( 'None', 'ofnoa-marquee' ),
						),
					),
					'logo_grayscale'  => array(
						'label'   => __( 'Grayscale', 'ofnoa-marquee' ),
						'type'    => 'range',
						'default' => 0,
						'min'     => 0,
						'max'     => 100,
						'unit'    => '%',
					),
					'logo_opacity'    => array(
						'label'   => __( 'Opacity', 'ofnoa-marquee' ),
						'type'    => 'range',
						'default' => 100,
						'min'     => 0,
						'max'     => 100,
						'unit'    => '%',
					),
					'logo_blend'      => array(
						'label'   => __( 'Blend mode', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'normal',
						'options' => array(
							'normal'     => __( 'Normal', 'ofnoa-marquee' ),
							'multiply'   => __( 'Multiply', 'ofnoa-marquee' ),
							'screen'     => __( 'Screen', 'ofnoa-marquee' ),
							'overlay'    => __( 'Overlay', 'ofnoa-marquee' ),
							'luminosity' => __( 'Luminosity', 'ofnoa-marquee' ),
						),
					),
					'hover_grayscale' => array(
						'label'   => __( 'Grayscale on hover', 'ofnoa-marquee' ),
						'type'    => 'range',
						'default' => 0,
						'min'     => 0,
						'max'     => 100,
						'unit'    => '%',
					),
					'hover_opacity'   => array(
						'label'   => __( 'Opacity on hover', 'ofnoa-marquee' ),
						'type'    => 'range',
						'default' => 100,
						'min'     => 0,
						'max'     => 100,
						'unit'    => '%',
					),
					'hover_scale'     => array(
						'label'   => __( 'Scale on hover', 'ofnoa-marquee' ),
						'type'    => 'range',
						'default' => 100,
						'min'     => 50,
						'max'     => 200,
						'unit'    => '%',
					),
					'item_bg'         => array(
						'label'   => __( 'Item background', 'ofnoa-marquee' ),
						'type'    => 'color',
						'default' => '',
					),
					'item_bg_hover'   => array(
						'label'   => __( 'Item background on hover', 'ofnoa-marquee' ),
						'type'    => 'color',
						'default' => '',
					),
					'item_radius'     => array(
						'label'   => __( 'Item radius', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 200,
						'unit'    => 'px',
					),
					'item_border_w'   => array(
						'label'   => __( 'Item border width', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 20,
						'unit'    => 'px',
					),
					'item_border_c'   => array(
						'label'   => __( 'Item border color', 'ofnoa-marquee' ),
						'type'    => 'color',
						'default' => '#e5e7eb',
					),
					'item_pad_x'      => array(
						'label'      => __( 'Item padding X', 'ofnoa-marquee' ),
						'type'       => 'number',
						'default'    => 0,
						'min'        => 0,
						'max'        => 120,
						'unit'       => 'px',
						'responsive' => true,
					),
					'item_pad_y'      => array(
						'label'   => __( 'Item padding Y', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 120,
						'unit'    => 'px',
					),
					'item_shadow'     => array(
						'label'   => __( 'Item shadow', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'none',
						'options' => array(
							'none' => __( 'None', 'ofnoa-marquee' ),
							'sm'   => __( 'Soft', 'ofnoa-marquee' ),
							'md'   => __( 'Medium', 'ofnoa-marquee' ),
							'lg'   => __( 'Strong', 'ofnoa-marquee' ),
						),
					),
					'transition_ms'   => array(
						'label'   => __( 'Hover transition', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 300,
						'min'     => 0,
						'max'     => 2000,
						'unit'    => 'ms',
					),
				),
			),

			'typography' => array(
				'label'  => __( 'Text & separator', 'ofnoa-marquee' ),
				'icon'   => 'dashicons-editor-textcolor',
				'fields' => array(
					'font_family'      => array(
						'label'       => __( 'Font family', 'ofnoa-marquee' ),
						'type'        => 'text',
						'default'     => '',
						'placeholder' => __( 'Inherit from theme', 'ofnoa-marquee' ),
					),
					'font_size'        => array(
						'label'      => __( 'Font size', 'ofnoa-marquee' ),
						'type'       => 'number',
						'default'    => 18,
						'min'        => 6,
						'max'        => 200,
						'unit'       => 'px',
						'responsive' => true,
					),
					'font_weight'      => array(
						'label'   => __( 'Font weight', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => '600',
						'options' => array(
							''    => __( 'Inherit', 'ofnoa-marquee' ),
							'300' => '300',
							'400' => '400',
							'500' => '500',
							'600' => '600',
							'700' => '700',
							'800' => '800',
							'900' => '900',
						),
					),
					'line_height'      => array(
						'label'   => __( 'Line height', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 1.4,
						'min'     => 0.8,
						'max'     => 3,
						'step'    => 0.1,
						'unit'    => '',
					),
					'letter_spacing'   => array(
						'label'   => __( 'Letter spacing', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => -5,
						'max'     => 20,
						'step'    => 0.1,
						'unit'    => 'px',
					),
					'text_transform'   => array(
						'label'   => __( 'Text transform', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'none',
						'options' => array(
							'none'       => __( 'None', 'ofnoa-marquee' ),
							'uppercase'  => __( 'UPPERCASE', 'ofnoa-marquee' ),
							'lowercase'  => __( 'lowercase', 'ofnoa-marquee' ),
							'capitalize' => __( 'Capitalize', 'ofnoa-marquee' ),
						),
					),
					'text_color'       => array(
						'label'   => __( 'Text color', 'ofnoa-marquee' ),
						'type'    => 'color',
						'default' => '#111827',
					),
					'text_hover_color' => array(
						'label'   => __( 'Text color on hover', 'ofnoa-marquee' ),
						'type'    => 'color',
						'default' => '#4f46e5',
					),
					'text_shadow'      => array(
						'label'       => __( 'Text shadow', 'ofnoa-marquee' ),
						'type'        => 'text',
						'default'     => '',
						'placeholder' => '0 2px 6px rgba(0,0,0,.25)',
					),
					'sep_type'         => array(
						'label'   => __( 'Separator', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'dot',
						'options' => array(
							'none' => __( 'None', 'ofnoa-marquee' ),
							'dot'  => __( 'Dot •', 'ofnoa-marquee' ),
							'dash' => __( 'Dash —', 'ofnoa-marquee' ),
							'star' => __( 'Star ★', 'ofnoa-marquee' ),
							'line' => __( 'Vertical line', 'ofnoa-marquee' ),
							'char' => __( 'Custom character', 'ofnoa-marquee' ),
						),
					),
					'sep_char'         => array(
						'label'     => __( 'Custom separator', 'ofnoa-marquee' ),
						'type'      => 'text',
						'default'   => '/',
						'condition' => array( 'sep_type' => array( 'char' ) ),
					),
					'sep_color'        => array(
						'label'     => __( 'Separator color', 'ofnoa-marquee' ),
						'type'      => 'color',
						'default'   => '#9ca3af',
						'condition' => array( 'sep_type' => array( 'dot', 'dash', 'star', 'line', 'char' ) ),
					),
					'sep_size'         => array(
						'label'     => __( 'Separator size', 'ofnoa-marquee' ),
						'type'      => 'number',
						'default'   => 18,
						'min'       => 2,
						'max'       => 120,
						'unit'      => 'px',
						'condition' => array( 'sep_type' => array( 'dot', 'dash', 'star', 'line', 'char' ) ),
					),
				),
			),

			'animation'  => array(
				'label'  => __( 'Animation', 'ofnoa-marquee' ),
				'icon'   => 'dashicons-controls-forward',
				'fields' => array(
					'speed'            => array(
						'label'      => __( 'Speed', 'ofnoa-marquee' ),
						'type'       => 'number',
						'default'    => 60,
						'min'        => 1,
						'max'        => 1000,
						'unit'       => 'px/s',
						'responsive' => true,
						'desc'       => __( 'Pixels per second — stays constant no matter how many items you add.', 'ofnoa-marquee' ),
					),
					'timing'           => array(
						'label'   => __( 'Timing function', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => 'linear',
						'options' => array(
							'linear'                        => __( 'Linear (classic marquee)', 'ofnoa-marquee' ),
							'ease-in-out'                   => __( 'Ease in-out', 'ofnoa-marquee' ),
							'cubic-bezier(.25,.1,.25,1)'    => __( 'Smooth', 'ofnoa-marquee' ),
							'steps(30)'                     => __( 'Stepped (retro)', 'ofnoa-marquee' ),
						),
					),
					'pause_on_hover'   => array(
						'label'   => __( 'Pause on hover', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 1,
					),
					'hover_speed'      => array(
						'label'     => __( 'Speed on hover', 'ofnoa-marquee' ),
						'type'      => 'range',
						'default'   => 100,
						'min'       => 5,
						'max'       => 300,
						'step'      => 5,
						'unit'      => '%',
						'desc'      => __( 'Used when "Pause on hover" is off — slow down or speed up instead.', 'ofnoa-marquee' ),
						'condition' => array( 'pause_on_hover' => array( 0 ) ),
					),
					'pause_on_click'   => array(
						'label'   => __( 'Toggle pause on click', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 0,
					),
					'start_delay'      => array(
						'label'   => __( 'Start delay', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 10000,
						'unit'    => 'ms',
					),
					'reverse_on_scroll' => array(
						'label'   => __( 'Reverse on scroll', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 0,
						'desc'    => __( 'Flips direction when the visitor scrolls up.', 'ofnoa-marquee' ),
					),
					'scroll_boost'     => array(
						'label'   => __( 'Speed boost while scrolling', 'ofnoa-marquee' ),
						'type'    => 'range',
						'default' => 100,
						'min'     => 100,
						'max'     => 500,
						'step'    => 10,
						'unit'    => '%',
					),
					'lazy_start'       => array(
						'label'   => __( 'Animate only when visible', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 1,
					),
					'fade_in'          => array(
						'label'   => __( 'Fade in on load', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 1,
					),
					'reduced_motion'   => array(
						'label'   => __( 'Respect "reduced motion"', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 1,
						'desc'    => __( 'Stops the animation for visitors who asked their OS to reduce motion.', 'ofnoa-marquee' ),
					),
				),
			),

			'advanced'   => array(
				'label'  => __( 'Advanced', 'ofnoa-marquee' ),
				'icon'   => 'dashicons-admin-generic',
				'fields' => array(
					'link_target'   => array(
						'label'   => __( 'Open links in', 'ofnoa-marquee' ),
						'type'    => 'select',
						'default' => '_self',
						'options' => array(
							'_self'  => __( 'Same tab', 'ofnoa-marquee' ),
							'_blank' => __( 'New tab', 'ofnoa-marquee' ),
						),
					),
					'link_nofollow' => array(
						'label'   => __( 'Add rel="nofollow"', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 0,
					),
					'aria_label'    => array(
						'label'       => __( 'ARIA label', 'ofnoa-marquee' ),
						'type'        => 'text',
						'default'     => '',
						'placeholder' => __( 'Our partners', 'ofnoa-marquee' ),
					),
					'extra_class'   => array(
						'label'   => __( 'Extra CSS class', 'ofnoa-marquee' ),
						'type'    => 'text',
						'default' => '',
					),
					'hide_desktop'  => array(
						'label'   => __( 'Hide on desktop', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 0,
					),
					'hide_tablet'   => array(
						'label'   => __( 'Hide on tablet', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 0,
					),
					'hide_mobile'   => array(
						'label'   => __( 'Hide on mobile', 'ofnoa-marquee' ),
						'type'    => 'toggle',
						'default' => 0,
					),
					'tablet_bp'     => array(
						'label'   => __( 'Tablet breakpoint', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 1024,
						'min'     => 480,
						'max'     => 1600,
						'unit'    => 'px',
					),
					'mobile_bp'     => array(
						'label'   => __( 'Mobile breakpoint', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 767,
						'min'     => 320,
						'max'     => 1024,
						'unit'    => 'px',
					),
					'z_index'       => array(
						'label'   => __( 'z-index (0 = auto)', 'ofnoa-marquee' ),
						'type'    => 'number',
						'default' => 0,
						'min'     => 0,
						'max'     => 999999,
					),
					'custom_css'    => array(
						'label'   => __( 'Custom CSS', 'ofnoa-marquee' ),
						'type'    => 'textarea',
						'default' => '',
						'desc'    => __( 'Use {{WRAPPER}} as a placeholder for this marquee, e.g. {{WRAPPER}} .omq__item { border-bottom: 2px solid red; }', 'ofnoa-marquee' ),
					),
				),
			),
		);

		/**
		 * Filter the field schema.
		 *
		 * @param array $schema Grouped field schema.
		 */
		return apply_filters( 'omq_field_schema', $schema );
	}

	/**
	 * Flatten the schema, expanding responsive variants.
	 *
	 * @return array key => definition
	 */
	public static function flat() {
		if ( null !== self::$flat ) {
			return self::$flat;
		}
		$flat = array();
		foreach ( self::schema() as $group_key => $group ) {
			foreach ( $group['fields'] as $key => $def ) {
				$def['group']    = $group_key;
				$flat[ $key ]    = $def;
				if ( ! empty( $def['responsive'] ) ) {
					foreach ( array( 'tablet', 'mobile' ) as $device ) {
						$child                        = $def;
						$child['is_responsive_child'] = true;
						$child['device']              = $device;
						$child['default']             = '';
						$flat[ $key . '_' . $device ] = $child;
					}
				}
			}
		}
		self::$flat = $flat;
		return $flat;
	}

	/**
	 * Default values for every field.
	 *
	 * @return array
	 */
	public static function defaults() {
		$out = array();
		foreach ( self::flat() as $key => $def ) {
			$out[ $key ] = isset( $def['default'] ) ? $def['default'] : '';
		}
		return $out;
	}

	/**
	 * Post type options for the dynamic source select.
	 *
	 * @return array
	 */
	public static function post_type_options() {
		$out   = array();
		$types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $types as $type ) {
			if ( OMQ_CPT === $type->name || 'attachment' === $type->name ) {
				continue;
			}
			$out[ $type->name ] = $type->labels->singular_name;
		}
		return $out;
	}

	/**
	 * Sanitize a raw settings array against the schema.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$flat = self::flat();
		$out  = self::defaults();

		foreach ( $flat as $key => $def ) {
			if ( ! isset( $input[ $key ] ) ) {
				if ( 'toggle' === $def['type'] ) {
					$out[ $key ] = 0;
				}
				continue;
			}
			$value = $input[ $key ];

			// Responsive children may stay empty = inherit from the base value.
			if ( ! empty( $def['is_responsive_child'] ) && ( '' === $value || null === $value ) ) {
				$out[ $key ] = '';
				continue;
			}

			switch ( $def['type'] ) {
				case 'toggle':
					$out[ $key ] = empty( $value ) ? 0 : 1;
					break;

				case 'number':
				case 'range':
					$step  = isset( $def['step'] ) ? (float) $def['step'] : 1;
					$value = ( $step < 1 ) ? (float) $value : (int) $value;
					if ( isset( $def['min'] ) && $value < $def['min'] ) {
						$value = $def['min'];
					}
					if ( isset( $def['max'] ) && $value > $def['max'] ) {
						$value = $def['max'];
					}
					$out[ $key ] = $value;
					break;

				case 'select':
					$options = self::field_options( $def );
					$value   = (string) $value;
					$out[ $key ] = array_key_exists( $value, $options ) ? $value : (string) $def['default'];
					break;

				case 'color':
					$out[ $key ] = self::sanitize_color( $value );
					break;

				case 'textarea':
					$out[ $key ] = wp_strip_all_tags( (string) $value );
					break;

				default:
					$out[ $key ] = sanitize_text_field( (string) $value );
					break;
			}
		}

		return $out;
	}

	/**
	 * Resolve a field's options (static or callback).
	 *
	 * @param array $def Field definition.
	 * @return array
	 */
	public static function field_options( $def ) {
		if ( ! empty( $def['options_cb'] ) && is_callable( $def['options_cb'] ) ) {
			return (array) call_user_func( $def['options_cb'] );
		}
		return isset( $def['options'] ) ? (array) $def['options'] : array();
	}

	/**
	 * Allow hex, rgb(a) and empty values.
	 *
	 * @param string $value Raw color.
	 * @return string
	 */
	public static function sanitize_color( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		$hex = sanitize_hex_color( $value );
		if ( $hex ) {
			return $hex;
		}
		if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/i', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Sanitize the repeater items array.
	 *
	 * @param array $items Raw items.
	 * @return array
	 */
	public static function sanitize_items( $items ) {
		$out = array();
		if ( ! is_array( $items ) ) {
			return $out;
		}
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$clean = array(
				'kind'     => in_array( isset( $item['kind'] ) ? $item['kind'] : 'image', array( 'image', 'text' ), true ) ? $item['kind'] : 'image',
				'image_id' => isset( $item['image_id'] ) ? absint( $item['image_id'] ) : 0,
				'text'     => isset( $item['text'] ) ? sanitize_text_field( $item['text'] ) : '',
				'alt'      => isset( $item['alt'] ) ? sanitize_text_field( $item['alt'] ) : '',
				'link'     => isset( $item['link'] ) ? esc_url_raw( $item['link'] ) : '',
				'target'   => ( isset( $item['target'] ) && '_blank' === $item['target'] ) ? '_blank' : '',
				'color'    => isset( $item['color'] ) ? self::sanitize_color( $item['color'] ) : '',
			);
			if ( 'image' === $clean['kind'] && ! $clean['image_id'] && '' === $clean['text'] ) {
				continue;
			}
			if ( 'text' === $clean['kind'] && '' === $clean['text'] ) {
				continue;
			}
			$out[] = $clean;
		}
		return $out;
	}

	/**
	 * Read merged settings for a marquee post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_settings( $post_id ) {
		$saved = get_post_meta( $post_id, '_omq_settings', true );
		$saved = is_array( $saved ) ? $saved : array();
		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Read items for a marquee post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_items( $post_id ) {
		$items = get_post_meta( $post_id, '_omq_items', true );
		return is_array( $items ) ? $items : array();
	}
}
