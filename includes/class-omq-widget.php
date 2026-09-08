<?php
/**
 * Classic sidebar widget.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Widget
 */
class OMQ_Widget {

	/**
	 * Hook.
	 */
	public static function init() {
		add_action(
			'widgets_init',
			function () {
				register_widget( 'OMQ_Marquee_Widget' );
			}
		);
	}
}

/**
 * Class OMQ_Marquee_Widget
 */
class OMQ_Marquee_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'omq_marquee_widget',
			__( 'Ofnoa Marquee', 'ofnoa-marquee' ),
			array( 'description' => __( 'Display a logo or text marquee.', 'ofnoa-marquee' ) )
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Widget args.
	 * @param array $instance Instance settings.
	 */
	public function widget( $args, $instance ) {
		$id = isset( $instance['marquee_id'] ) ? absint( $instance['marquee_id'] ) : 0;
		if ( ! $id ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo OMQ_Render::render_post( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Admin form.
	 *
	 * @param array $instance Instance settings.
	 */
	public function form( $instance ) {
		$title    = isset( $instance['title'] ) ? $instance['title'] : '';
		$selected = isset( $instance['marquee_id'] ) ? absint( $instance['marquee_id'] ) : 0;
		$marquees = get_posts(
			array(
				'post_type'      => OMQ_CPT,
				'posts_per_page' => 200,
				'post_status'    => 'publish',
			)
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'ofnoa-marquee' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'marquee_id' ) ); ?>"><?php esc_html_e( 'Marquee:', 'ofnoa-marquee' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'marquee_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'marquee_id' ) ); ?>">
				<option value="0"><?php esc_html_e( '— Select —', 'ofnoa-marquee' ); ?></option>
				<?php foreach ( $marquees as $marquee ) : ?>
					<option value="<?php echo (int) $marquee->ID; ?>" <?php selected( $selected, $marquee->ID ); ?>>
						<?php echo esc_html( $marquee->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Old values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		return array(
			'title'      => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'marquee_id' => isset( $new_instance['marquee_id'] ) ? absint( $new_instance['marquee_id'] ) : 0,
		);
	}
}
