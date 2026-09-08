<?php
/**
 * Self-updating from GitHub releases.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Updater
 */
class OMQ_Updater {

	/**
	 * Transient key.
	 */
	const CACHE_KEY = 'omq_gh_release';

	/**
	 * Cache lifetime in seconds.
	 */
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * Hook.
	 */
	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_folder_name' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
	}

	/**
	 * Whether update checks are enabled.
	 *
	 * @return bool
	 */
	protected static function enabled() {
		$options = OMQ_Tools::options();
		return empty( $options['disable_updates'] );
	}

	/**
	 * Request headers (adds a token for private repos).
	 *
	 * @return array
	 */
	protected static function headers() {
		$headers = array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'Ofnoa-Marquee/' . OMQ_VERSION . '; ' . home_url( '/' ),
		);

		$options = OMQ_Tools::options();
		if ( ! empty( $options['github_token'] ) ) {
			$headers['Authorization'] = 'Bearer ' . $options['github_token'];
		}

		return $headers;
	}

	/**
	 * Fetch the latest release from GitHub (cached).
	 *
	 * @param bool $force Skip the cache.
	 * @return array|WP_Error
	 */
	public static function remote_release( $force = false ) {
		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . OMQ_GH_REPO . '/releases/latest',
			array(
				'timeout' => 12,
				'headers' => self::headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return new WP_Error( 'omq_gh_http', 'GitHub responded with ' . $code );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			return new WP_Error( 'omq_gh_parse', 'Unexpected GitHub payload' );
		}

		$version = ltrim( $body['tag_name'], 'vV' );
		$package = isset( $body['zipball_url'] ) ? $body['zipball_url'] : '';

		// Prefer a built asset ZIP when the release ships one.
		if ( ! empty( $body['assets'] ) && is_array( $body['assets'] ) ) {
			foreach ( $body['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] ) && preg_match( '/\.zip$/i', $asset['browser_download_url'] ) ) {
					$package = $asset['browser_download_url'];
					break;
				}
			}
		}

		$release = array(
			'version'   => $version,
			'package'   => $package,
			'changelog' => isset( $body['body'] ) ? (string) $body['body'] : '',
			'published' => isset( $body['published_at'] ) ? $body['published_at'] : '',
			'url'       => isset( $body['html_url'] ) ? $body['html_url'] : 'https://github.com/' . OMQ_GH_REPO,
		);

		set_site_transient( self::CACHE_KEY, $release, self::CACHE_TTL );

		return $release;
	}

	/**
	 * Inject the update into WordPress' update list.
	 *
	 * @param object $transient Update transient.
	 * @return object
	 */
	public static function check( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}
		if ( ! self::enabled() ) {
			return $transient;
		}

		$release = self::remote_release();
		if ( is_wp_error( $release ) || empty( $release['version'] ) || empty( $release['package'] ) ) {
			return $transient;
		}

		if ( ! version_compare( $release['version'], OMQ_VERSION, '>' ) ) {
			if ( isset( $transient->response[ OMQ_BASENAME ] ) ) {
				unset( $transient->response[ OMQ_BASENAME ] );
			}
			return $transient;
		}

		$item = array(
			'id'            => 'github.com/' . OMQ_GH_REPO,
			'slug'          => OMQ_SLUG,
			'plugin'        => OMQ_BASENAME,
			'new_version'   => $release['version'],
			'url'           => $release['url'],
			'package'       => $release['package'],
			'icons'         => array(),
			'banners'       => array(),
			'tested'        => get_bloginfo( 'version' ),
			'requires_php'  => '7.2',
			'compatibility' => new stdClass(),
		);

		$transient->response[ OMQ_BASENAME ] = (object) $item;

		return $transient;
	}

	/**
	 * "View details" popup content.
	 *
	 * @param false|object|array $result Result.
	 * @param string             $action Action.
	 * @param object             $args   Args.
	 * @return false|object|array
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || OMQ_SLUG !== $args->slug ) {
			return $result;
		}

		$release = self::remote_release();
		if ( is_wp_error( $release ) ) {
			return $result;
		}

		$changelog = $release['changelog'] ? $release['changelog'] : __( 'See the GitHub release notes.', 'ofnoa-marquee' );

		return (object) array(
			'name'           => 'Ofnoa Marquee — Logo & Text Ticker',
			'slug'           => OMQ_SLUG,
			'version'        => $release['version'],
			'author'         => '<a href="https://github.com/lirish1973">Liraz</a>',
			'homepage'       => $release['url'],
			'download_link'  => $release['package'],
			'requires'       => '5.8',
			'requires_php'   => '7.2',
			'last_updated'   => $release['published'],
			'sections'       => array(
				'description' => __( 'Professional, fully customizable marquee / ticker for logos and text.', 'ofnoa-marquee' ),
				'changelog'   => '<pre style="white-space:pre-wrap">' . esc_html( $changelog ) . '</pre>',
			),
		);
	}

	/**
	 * GitHub ships folders like "user-repo-abc1234" — rename to the plugin slug.
	 *
	 * @param string $source        Source folder.
	 * @param string $remote_source Remote source.
	 * @param object $upgrader      Upgrader.
	 * @param array  $hook_extra    Extra data.
	 * @return string|WP_Error
	 */
	public static function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		if ( empty( $hook_extra['plugin'] ) || OMQ_BASENAME !== $hook_extra['plugin'] ) {
			return $source;
		}
		if ( ! $wp_filesystem ) {
			return $source;
		}

		$desired = trailingslashit( $remote_source ) . OMQ_SLUG;

		if ( trailingslashit( $source ) === trailingslashit( $desired ) ) {
			return $source;
		}

		if ( $wp_filesystem->is_dir( $desired ) ) {
			$wp_filesystem->delete( $desired, true );
		}

		if ( $wp_filesystem->move( $source, $desired ) ) {
			return trailingslashit( $desired );
		}

		return new WP_Error( 'omq_rename_failed', __( 'Could not rename the plugin folder during update.', 'ofnoa-marquee' ) );
	}

	/**
	 * Clear the cache after an update.
	 *
	 * @param object $upgrader Upgrader.
	 * @param array  $options  Options.
	 */
	public static function clear_cache( $upgrader, $options ) {
		if ( isset( $options['action'], $options['type'] ) && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			delete_site_transient( self::CACHE_KEY );
		}
	}

	/**
	 * Add a GitHub link on the plugins screen.
	 *
	 * @param array  $links Links.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public static function row_meta( $links, $file ) {
		if ( OMQ_BASENAME !== $file ) {
			return $links;
		}
		$links[] = '<a href="https://github.com/' . esc_attr( OMQ_GH_REPO ) . '" target="_blank" rel="noopener">' . esc_html__( 'GitHub', 'ofnoa-marquee' ) . '</a>';
		return $links;
	}
}
