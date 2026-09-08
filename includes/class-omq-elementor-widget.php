<?php
/**
 * Elementor widget.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Elementor_Widget
 */
class OMQ_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ofnoa_marquee';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Ofnoa Marquee', 'ofnoa-marquee' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'marquee', 'ticker', 'logo', 'slider', 'scroll', 'carousel' );
	}

	/**
	 * Script deps.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'omq-frontend' );
	}

	/**
	 * Style deps.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'omq-frontend' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'omq_source',
			array( 'label' => __( 'Marquee', 'ofnoa-marquee' ) )
		);

		$this->add_control(
			'marquee_id',
			array(
				'label'   => __( 'Select marquee', 'ofnoa-marquee' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => OMQ_Elementor::options(),
				'default' => 0,
			)
		);

		$this->add_control(
			'omq_edit_link',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . OMQ_CPT ) ) . '" target="_blank">' . esc_html__( 'Manage marquees →', 'ofnoa-marquee' ) . '</a>',
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'omq_overrides',
			array( 'label' => __( 'Overrides', 'ofnoa-marquee' ) )
		);

		$this->add_control(
			'direction',
			array(
				'label'   => __( 'Direction', 'ofnoa-marquee' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''      => __( 'Use saved value', 'ofnoa-marquee' ),
					'left'  => __( 'Right to left', 'ofnoa-marquee' ),
					'right' => __( 'Left to right', 'ofnoa-marquee' ),
					'up'    => __( 'Bottom to top', 'ofnoa-marquee' ),
					'down'  => __( 'Top to bottom', 'ofnoa-marquee' ),
				),
			)
		);

		$this->add_control(
			'rows',
			array(
				'label'   => __( 'Rows', 'ofnoa-marquee' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''  => __( 'Use saved value', 'ofnoa-marquee' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
				),
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'       => __( 'Speed (px/s)', 'ofnoa-marquee' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 1000,
				'default'     => '',
				'description' => __( 'Leave empty to keep the saved value.', 'ofnoa-marquee' ),
			)
		);

		$this->add_control(
			'gap',
			array(
				'label'   => __( 'Gap between items (px)', 'ofnoa-marquee' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 300,
				'default' => '',
			)
		);

		$this->add_control(
			'logo_height',
			array(
				'label'   => __( 'Logo height (px)', 'ofnoa-marquee' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 10,
				'max'     => 600,
				'default' => '',
			)
		);

		$this->add_control(
			'font_size',
			array(
				'label'   => __( 'Font size (px)', 'ofnoa-marquee' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 6,
				'max'     => 200,
				'default' => '',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'   => __( 'Text color', 'ofnoa-marquee' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
			)
		);

		$this->add_control(
			'text_hover_color',
			array(
				'label'   => __( 'Text hover color', 'ofnoa-marquee' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'   => __( 'Background color', 'ofnoa-marquee' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
			)
		);

		$this->add_control(
			'fade_edges',
			array(
				'label'        => __( 'Fade edges', 'ofnoa-marquee' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => '1',
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'        => __( 'Pause on hover', 'ofnoa-marquee' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => '1',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Front-end render.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$id       = isset( $settings['marquee_id'] ) ? absint( $settings['marquee_id'] ) : 0;

		if ( ! $id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Select a marquee to display.', 'ofnoa-marquee' ) . '</div>';
			}
			return;
		}

		$keys      = array( 'direction', 'rows', 'speed', 'gap', 'logo_height', 'font_size', 'text_color', 'text_hover_color', 'bg_color', 'fade_edges', 'pause_on_hover' );
		$overrides = array();

		foreach ( $keys as $key ) {
			if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] && null !== $settings[ $key ] ) {
				$overrides[ $key ] = $settings[ $key ];
			}
		}

		if ( ! empty( $overrides['bg_color'] ) ) {
			$overrides['bg_type'] = 'solid';
		}

		echo OMQ_Render::render_post( $id, $overrides ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
