<?php
/**
 * Tools screen: global options, import / export, update check.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Tools
 */
class OMQ_Tools {

	/**
	 * Hook.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_omq_save_options', array( __CLASS__, 'save_options' ) );
		add_action( 'admin_post_omq_export', array( __CLASS__, 'export' ) );
		add_action( 'admin_post_omq_import', array( __CLASS__, 'import' ) );
		add_action( 'admin_post_omq_check_update', array( __CLASS__, 'check_update' ) );
	}

	/**
	 * Get global options.
	 *
	 * @return array
	 */
	public static function options() {
		return wp_parse_args(
			(array) get_option( 'omq_options', array() ),
			array(
				'delete_on_uninstall' => 0,
				'github_token'        => '',
				'disable_updates'     => 0,
			)
		);
	}

	/**
	 * Add the submenu page.
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . OMQ_CPT,
			__( 'Marquee Tools', 'ofnoa-marquee' ),
			__( 'Tools', 'ofnoa-marquee' ),
			'manage_options',
			'omq-tools',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Render the tools page.
	 */
	public static function render() {
		$options  = self::options();
		$marquees = get_posts(
			array(
				'post_type'      => OMQ_CPT,
				'posts_per_page' => 300,
				'post_status'    => array( 'publish', 'draft' ),
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ofnoa Marquee — Tools', 'ofnoa-marquee' ); ?></h1>

			<?php if ( isset( $_GET['omq_msg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['omq_msg'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?></p>
				</div>
			<?php endif; ?>

			<div class="omq-tools-card">
				<h2><?php esc_html_e( 'Settings', 'ofnoa-marquee' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="omq_save_options">
					<?php wp_nonce_field( 'omq_options' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Delete data on uninstall', 'ofnoa-marquee' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( $options['delete_on_uninstall'], 1 ); ?>>
									<?php esc_html_e( 'Remove all marquees and settings when the plugin is deleted', 'ofnoa-marquee' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'GitHub updates', 'ofnoa-marquee' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="disable_updates" value="1" <?php checked( $options['disable_updates'], 1 ); ?>>
									<?php esc_html_e( 'Disable automatic update checks from GitHub', 'ofnoa-marquee' ); ?>
								</label>
								<p class="description">
									<?php
									printf(
										/* translators: %s: repository slug */
										esc_html__( 'Updates are pulled from GitHub releases of %s.', 'ofnoa-marquee' ),
										'<code>' . esc_html( OMQ_GH_REPO ) . '</code>'
									);
									?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="omq-token"><?php esc_html_e( 'GitHub token (private repos only)', 'ofnoa-marquee' ); ?></label></th>
							<td>
								<input type="password" id="omq-token" class="regular-text" name="github_token" value="<?php echo esc_attr( $options['github_token'] ); ?>" autocomplete="new-password">
								<p class="description"><?php esc_html_e( 'Leave empty for public repositories.', 'ofnoa-marquee' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>

				<p>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=omq_check_update' ), 'omq_check_update' ) ); ?>">
						<?php esc_html_e( 'Check for updates now', 'ofnoa-marquee' ); ?>
					</a>
					<span class="description">
						<?php
						printf(
							/* translators: %s: version */
							esc_html__( 'Installed version: %s', 'ofnoa-marquee' ),
							'<code>' . esc_html( OMQ_VERSION ) . '</code>'
						);
						?>
					</span>
				</p>
			</div>

			<div class="omq-tools-card">
				<h2><?php esc_html_e( 'Export', 'ofnoa-marquee' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="omq_export">
					<?php wp_nonce_field( 'omq_export' ); ?>
					<p>
						<select name="marquee_ids[]" multiple size="6" style="min-width:320px">
							<?php foreach ( $marquees as $marquee ) : ?>
								<option value="<?php echo (int) $marquee->ID; ?>"><?php echo esc_html( $marquee->post_title ? $marquee->post_title : '#' . $marquee->ID ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<p class="description"><?php esc_html_e( 'Select nothing to export everything.', 'ofnoa-marquee' ); ?></p>
					<?php submit_button( __( 'Download JSON', 'ofnoa-marquee' ), 'secondary' ); ?>
				</form>
			</div>

			<div class="omq-tools-card">
				<h2><?php esc_html_e( 'Import', 'ofnoa-marquee' ); ?></h2>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="omq_import">
					<?php wp_nonce_field( 'omq_import' ); ?>
					<p><input type="file" name="omq_file" accept="application/json,.json" required></p>
					<p class="description"><?php esc_html_e( 'Images are matched by attachment ID — re-select them if you import into a different site.', 'ofnoa-marquee' ); ?></p>
					<?php submit_button( __( 'Import', 'ofnoa-marquee' ), 'secondary' ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Save global options.
	 */
	public static function save_options() {
		check_admin_referer( 'omq_options' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ofnoa-marquee' ) );
		}

		update_option(
			'omq_options',
			array(
				'delete_on_uninstall' => isset( $_POST['delete_on_uninstall'] ) ? 1 : 0,
				'disable_updates'     => isset( $_POST['disable_updates'] ) ? 1 : 0,
				'github_token'        => isset( $_POST['github_token'] ) ? sanitize_text_field( wp_unslash( $_POST['github_token'] ) ) : '',
			)
		);

		delete_site_transient( 'omq_gh_release' );
		self::redirect( __( 'Settings saved.', 'ofnoa-marquee' ) );
	}

	/**
	 * Force a fresh update check.
	 */
	public static function check_update() {
		check_admin_referer( 'omq_check_update' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ofnoa-marquee' ) );
		}

		delete_site_transient( 'omq_gh_release' );
		delete_site_transient( 'update_plugins' );

		$release = OMQ_Updater::remote_release( true );
		if ( is_wp_error( $release ) || empty( $release['version'] ) ) {
			self::redirect( __( 'Could not reach GitHub right now.', 'ofnoa-marquee' ) );
		}

		if ( version_compare( $release['version'], OMQ_VERSION, '>' ) ) {
			/* translators: %s: version number */
			self::redirect( sprintf( __( 'Version %s is available — go to Dashboard → Updates.', 'ofnoa-marquee' ), $release['version'] ) );
		}

		self::redirect( __( 'You are running the latest version.', 'ofnoa-marquee' ) );
	}

	/**
	 * Export marquees to JSON.
	 */
	public static function export() {
		check_admin_referer( 'omq_export' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ofnoa-marquee' ) );
		}

		$ids = isset( $_POST['marquee_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['marquee_ids'] ) ) : array();

		$args = array(
			'post_type'      => OMQ_CPT,
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft' ),
		);
		if ( $ids ) {
			$args['post__in'] = $ids;
		}

		$data = array(
			'plugin'   => 'ofnoa-marquee',
			'version'  => OMQ_VERSION,
			'exported' => gmdate( 'c' ),
			'marquees' => array(),
		);

		foreach ( get_posts( $args ) as $marquee ) {
			$data['marquees'][] = array(
				'title'    => $marquee->post_title,
				'settings' => OMQ_Fields::get_settings( $marquee->ID ),
				'items'    => OMQ_Fields::get_items( $marquee->ID ),
			);
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=ofnoa-marquee-' . gmdate( 'Y-m-d' ) . '.json' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Import marquees from JSON.
	 */
	public static function import() {
		check_admin_referer( 'omq_import' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ofnoa-marquee' ) );
		}

		if ( empty( $_FILES['omq_file']['tmp_name'] ) ) {
			self::redirect( __( 'No file uploaded.', 'ofnoa-marquee' ) );
		}

		$raw = file_get_contents( $_FILES['omq_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || empty( $data['marquees'] ) ) {
			self::redirect( __( 'That file does not look like a marquee export.', 'ofnoa-marquee' ) );
		}

		$count = 0;
		foreach ( $data['marquees'] as $marquee ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => OMQ_CPT,
					'post_status' => 'publish',
					'post_title'  => isset( $marquee['title'] ) ? sanitize_text_field( $marquee['title'] ) : __( 'Imported marquee', 'ofnoa-marquee' ),
				)
			);

			if ( ! $post_id || is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_omq_settings', OMQ_Fields::sanitize( isset( $marquee['settings'] ) ? (array) $marquee['settings'] : array() ) );
			update_post_meta( $post_id, '_omq_items', OMQ_Fields::sanitize_items( isset( $marquee['items'] ) ? (array) $marquee['items'] : array() ) );
			$count++;
		}

		/* translators: %d: number of imported marquees */
		self::redirect( sprintf( _n( '%d marquee imported.', '%d marquees imported.', $count, 'ofnoa-marquee' ), $count ) );
	}

	/**
	 * Redirect back to the tools page with a message.
	 *
	 * @param string $message Message.
	 */
	protected static function redirect( $message ) {
		wp_safe_redirect(
			add_query_arg(
				'omq_msg',
				rawurlencode( $message ),
				admin_url( 'edit.php?post_type=' . OMQ_CPT . '&page=omq-tools' )
			)
		);
		exit;
	}
}
