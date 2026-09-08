<?php
/**
 * Uninstall routine.
 *
 * @package Ofnoa_Marquee
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$omq_options = get_option( 'omq_options', array() );

if ( empty( $omq_options['delete_on_uninstall'] ) ) {
	return;
}

$omq_posts = get_posts(
	array(
		'post_type'      => 'omq_marquee',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

foreach ( $omq_posts as $omq_post_id ) {
	wp_delete_post( $omq_post_id, true );
}

delete_option( 'omq_options' );
delete_site_transient( 'omq_gh_release' );
