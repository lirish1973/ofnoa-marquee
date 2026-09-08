<?php
/**
 * Marquee custom post type, admin columns and duplication.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Post_Type
 */
class OMQ_Post_Type {

	/**
	 * Hook everything.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_filter( 'manage_' . OMQ_CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . OMQ_CPT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_action_omq_duplicate', array( __CLASS__, 'duplicate' ) );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
	}

	/**
	 * Register the CPT.
	 */
	public static function register_cpt() {
		$labels = array(
			'name'               => __( 'Marquees', 'ofnoa-marquee' ),
			'singular_name'      => __( 'Marquee', 'ofnoa-marquee' ),
			'add_new'            => __( 'Add New', 'ofnoa-marquee' ),
			'add_new_item'       => __( 'Add New Marquee', 'ofnoa-marquee' ),
			'edit_item'          => __( 'Edit Marquee', 'ofnoa-marquee' ),
			'new_item'           => __( 'New Marquee', 'ofnoa-marquee' ),
			'view_item'          => __( 'View Marquee', 'ofnoa-marquee' ),
			'search_items'       => __( 'Search Marquees', 'ofnoa-marquee' ),
			'not_found'          => __( 'No marquees yet', 'ofnoa-marquee' ),
			'not_found_in_trash' => __( 'No marquees in trash', 'ofnoa-marquee' ),
			'menu_name'          => __( 'Marquee', 'ofnoa-marquee' ),
		);

		register_post_type(
			OMQ_CPT,
			array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => false,
				'menu_icon'          => 'dashicons-controls-repeat',
				'menu_position'      => 26,
				'supports'           => array( 'title' ),
				'capability_type'    => 'post',
				'has_archive'        => false,
				'rewrite'            => false,
				'query_var'          => false,
			)
		);
	}

	/**
	 * All marquees an editor may pick from — including drafts, which would
	 * otherwise be invisible in every picker while still being editable.
	 *
	 * @return WP_Post[]
	 */
	public static function get_marquees() {
		return get_posts(
			array(
				'post_type'        => OMQ_CPT,
				'posts_per_page'   => 200,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);
	}

	/**
	 * A marquee's label for a picker, flagging anything not published.
	 *
	 * @param WP_Post $marquee Marquee post.
	 * @return string
	 */
	public static function label( $marquee ) {
		$title = $marquee->post_title ? $marquee->post_title : sprintf( '#%d', $marquee->ID );

		if ( 'publish' !== $marquee->post_status ) {
			$statuses = array(
				'draft'   => __( 'draft — not visible to visitors', 'ofnoa-marquee' ),
				'pending' => __( 'pending review', 'ofnoa-marquee' ),
				'private' => __( 'private', 'ofnoa-marquee' ),
			);
			$note     = isset( $statuses[ $marquee->post_status ] ) ? $statuses[ $marquee->post_status ] : $marquee->post_status;
			$title   .= ' (' . $note . ')';
		}

		return $title;
	}

	/**
	 * Title placeholder.
	 *
	 * @param string  $text Placeholder.
	 * @param WP_Post $post Post.
	 * @return string
	 */
	public static function title_placeholder( $text, $post ) {
		if ( $post && OMQ_CPT === $post->post_type ) {
			return __( 'Marquee name (internal)', 'ofnoa-marquee' );
		}
		return $text;
	}

	/**
	 * Admin list columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['omq_type']      = __( 'Type', 'ofnoa-marquee' );
				$new['omq_items']     = __( 'Items', 'ofnoa-marquee' );
				$new['omq_shortcode'] = __( 'Shortcode', 'ofnoa-marquee' );
			}
		}
		return $new;
	}

	/**
	 * Render custom columns.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'omq_type':
				$settings = OMQ_Fields::get_settings( $post_id );
				$map      = array(
					'logos' => __( 'Logos', 'ofnoa-marquee' ),
					'text'  => __( 'Text', 'ofnoa-marquee' ),
					'both'  => __( 'Mixed', 'ofnoa-marquee' ),
				);
				$type     = isset( $map[ $settings['display_mode'] ] ) ? $map[ $settings['display_mode'] ] : $settings['display_mode'];
				$dir      = array(
					'left'  => '←',
					'right' => '→',
					'up'    => '↑',
					'down'  => '↓',
				);
				echo esc_html( $type ) . ' ' . esc_html( isset( $dir[ $settings['direction'] ] ) ? $dir[ $settings['direction'] ] : '' );
				break;

			case 'omq_items':
				$settings = OMQ_Fields::get_settings( $post_id );
				if ( 'posts' === $settings['content_source'] ) {
					echo esc_html__( 'Dynamic', 'ofnoa-marquee' );
				} else {
					echo (int) count( OMQ_Fields::get_items( $post_id ) );
				}
				break;

			case 'omq_shortcode':
				printf(
					'<code class="omq-copy" data-clipboard="%1$s" title="%2$s">%1$s</code>',
					esc_attr( '[ofnoa_marquee id="' . $post_id . '"]' ),
					esc_attr__( 'Click to copy', 'ofnoa-marquee' )
				);
				break;
		}
	}

	/**
	 * Add a "Duplicate" row action.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( OMQ_CPT !== $post->post_type || ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}
		$url = wp_nonce_url(
			admin_url( 'admin.php?action=omq_duplicate&post=' . $post->ID ),
			'omq_duplicate_' . $post->ID
		);
		$actions['omq_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'ofnoa-marquee' ) . '</a>';
		return $actions;
	}

	/**
	 * Duplicate a marquee.
	 */
	public static function duplicate() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'omq_duplicate_' . $post_id );

		if ( ! $post_id || ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ofnoa-marquee' ) );
		}
		$post = get_post( $post_id );
		if ( ! $post || OMQ_CPT !== $post->post_type ) {
			wp_die( esc_html__( 'Marquee not found.', 'ofnoa-marquee' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => OMQ_CPT,
				'post_title'  => $post->post_title . ' ' . __( '(copy)', 'ofnoa-marquee' ),
				'post_status' => 'draft',
			)
		);

		if ( $new_id && ! is_wp_error( $new_id ) ) {
			update_post_meta( $new_id, '_omq_settings', OMQ_Fields::get_settings( $post_id ) );
			update_post_meta( $new_id, '_omq_items', OMQ_Fields::get_items( $post_id ) );
		}

		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
		exit;
	}
}
