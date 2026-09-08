<?php
/**
 * Admin builder UI: tabs, item repeater, live preview.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Metabox
 */
class OMQ_Metabox {

	/**
	 * Hook.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_boxes' ) );
		add_action( 'save_post_' . OMQ_CPT, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_omq_preview', array( __CLASS__, 'ajax_preview' ) );
	}

	/**
	 * Register meta boxes.
	 */
	public static function add_boxes() {
		add_meta_box(
			'omq-preview',
			__( 'Live preview', 'ofnoa-marquee' ),
			array( __CLASS__, 'render_preview_box' ),
			OMQ_CPT,
			'normal',
			'high'
		);

		add_meta_box(
			'omq-builder',
			__( 'Marquee builder', 'ofnoa-marquee' ),
			array( __CLASS__, 'render_builder' ),
			OMQ_CPT,
			'normal',
			'high'
		);

		add_meta_box(
			'omq-embed',
			__( 'How to embed', 'ofnoa-marquee' ),
			array( __CLASS__, 'render_embed_box' ),
			OMQ_CPT,
			'side',
			'high'
		);
	}

	/**
	 * Enqueue admin assets on the marquee screens.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || OMQ_CPT !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'omq-frontend', OMQ_URL . 'assets/css/omq-frontend.css', array(), OMQ_VERSION );
		wp_enqueue_style( 'omq-admin', OMQ_URL . 'assets/css/omq-admin.css', array( 'wp-color-picker' ), OMQ_VERSION );

		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'omq-frontend', OMQ_URL . 'assets/js/omq-frontend.js', array(), OMQ_VERSION, true );
			wp_enqueue_script(
				'omq-admin',
				OMQ_URL . 'assets/js/omq-admin.js',
				array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable', 'omq-frontend' ),
				OMQ_VERSION,
				true
			);
			wp_localize_script(
				'omq-admin',
				'omqAdmin',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'omq_preview' ),
					'i18n'    => array(
						'chooseImage' => __( 'Choose image', 'ofnoa-marquee' ),
						'useImage'    => __( 'Use this image', 'ofnoa-marquee' ),
						'confirmDel'  => __( 'Remove this item?', 'ofnoa-marquee' ),
						'copied'      => __( 'Copied!', 'ofnoa-marquee' ),
						'newItem'     => __( 'New item', 'ofnoa-marquee' ),
					),
				)
			);
		} else {
			wp_enqueue_script( 'omq-admin-list', OMQ_URL . 'assets/js/omq-admin.js', array( 'jquery' ), OMQ_VERSION, true );
		}
	}

	/**
	 * Preview meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_preview_box( $post ) {
		?>
		<div class="omq-preview-box">
			<div class="omq-preview-toolbar">
				<button type="button" class="button button-secondary" id="omq-refresh-preview">
					<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Refresh preview', 'ofnoa-marquee' ); ?>
				</button>
				<label class="omq-inline-label">
					<input type="checkbox" id="omq-auto-preview" checked> <?php esc_html_e( 'Auto refresh', 'ofnoa-marquee' ); ?>
				</label>
				<span class="omq-preview-devices">
					<button type="button" class="button omq-device is-active" data-width="100%"><span class="dashicons dashicons-desktop"></span></button>
					<button type="button" class="button omq-device" data-width="820px"><span class="dashicons dashicons-tablet"></span></button>
					<button type="button" class="button omq-device" data-width="400px"><span class="dashicons dashicons-smartphone"></span></button>
				</span>
				<span class="spinner" id="omq-preview-spinner"></span>
			</div>
			<div class="omq-preview-stage">
				<div class="omq-preview-wrap" id="omq-preview" style="width:100%">
					<p class="omq-preview-empty"><?php esc_html_e( 'Add items and the preview will appear here.', 'ofnoa-marquee' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Embed helper meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_embed_box( $post ) {
		$shortcode = '[ofnoa_marquee id="' . $post->ID . '"]';
		$php       = 'echo do_shortcode( \'' . $shortcode . '\' );';
		?>
		<p><strong><?php esc_html_e( 'Shortcode', 'ofnoa-marquee' ); ?></strong></p>
		<p><code class="omq-copy" data-clipboard="<?php echo esc_attr( $shortcode ); ?>"><?php echo esc_html( $shortcode ); ?></code></p>

		<p><strong><?php esc_html_e( 'PHP template', 'ofnoa-marquee' ); ?></strong></p>
		<p><code class="omq-copy" data-clipboard="<?php echo esc_attr( $php ); ?>"><?php echo esc_html( $php ); ?></code></p>

		<p><strong><?php esc_html_e( 'Gutenberg', 'ofnoa-marquee' ); ?></strong><br>
			<?php esc_html_e( 'Add the "Ofnoa Marquee" block and pick this marquee.', 'ofnoa-marquee' ); ?></p>

		<p><strong><?php esc_html_e( 'Elementor', 'ofnoa-marquee' ); ?></strong><br>
			<?php esc_html_e( 'Search for the "Ofnoa Marquee" widget in the General category.', 'ofnoa-marquee' ); ?></p>

		<p class="description">
			<?php esc_html_e( 'Shortcode attributes can override any setting, e.g.', 'ofnoa-marquee' ); ?>
			<code>[ofnoa_marquee id="<?php echo (int) $post->ID; ?>" speed="120" direction="right"]</code>
		</p>
		<?php
	}

	/**
	 * The main builder UI.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_builder( $post ) {
		wp_nonce_field( 'omq_save_' . $post->ID, 'omq_nonce' );

		$settings = OMQ_Fields::get_settings( $post->ID );
		$items    = OMQ_Fields::get_items( $post->ID );
		$schema   = OMQ_Fields::schema();
		?>
		<div class="omq-builder">
			<nav class="omq-tabs">
				<button type="button" class="omq-tab is-active" data-tab="items">
					<span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Items', 'ofnoa-marquee' ); ?>
				</button>
				<?php foreach ( $schema as $group_key => $group ) : ?>
					<button type="button" class="omq-tab" data-tab="<?php echo esc_attr( $group_key ); ?>">
						<span class="dashicons <?php echo esc_attr( $group['icon'] ); ?>"></span> <?php echo esc_html( $group['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</nav>

			<div class="omq-panels">
				<div class="omq-panel is-active" data-panel="items">
					<?php self::render_items_panel( $items ); ?>
				</div>

				<?php foreach ( $schema as $group_key => $group ) : ?>
					<div class="omq-panel" data-panel="<?php echo esc_attr( $group_key ); ?>">
						<div class="omq-grid">
							<?php
							foreach ( $group['fields'] as $key => $def ) {
								self::render_field( $key, $def, $settings );
							}
							?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Items repeater panel.
	 *
	 * @param array $items Saved items.
	 */
	protected static function render_items_panel( $items ) {
		?>
		<div class="omq-items-toolbar">
			<button type="button" class="button button-primary" id="omq-add-item">
				<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add item', 'ofnoa-marquee' ); ?>
			</button>
			<button type="button" class="button" id="omq-add-images">
				<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'Bulk add images', 'ofnoa-marquee' ); ?>
			</button>
			<button type="button" class="button" id="omq-add-text">
				<span class="dashicons dashicons-editor-textcolor"></span> <?php esc_html_e( 'Add text item', 'ofnoa-marquee' ); ?>
			</button>
			<span class="description"><?php esc_html_e( 'Drag items to reorder.', 'ofnoa-marquee' ); ?></span>
		</div>

		<div class="omq-items" id="omq-items">
			<?php
			if ( empty( $items ) ) {
				echo '<p class="omq-items-empty">' . esc_html__( 'No items yet — add logos or text above.', 'ofnoa-marquee' ) . '</p>';
			}
			foreach ( $items as $index => $item ) {
				self::render_item_row( $index, $item );
			}
			?>
		</div>

		<script type="text/html" id="tmpl-omq-item">
			<?php
			self::render_item_row(
				'{{INDEX}}',
				array(
					'kind'     => 'image',
					'image_id' => 0,
					'text'     => '',
					'alt'      => '',
					'link'     => '',
					'target'   => '',
					'color'    => '',
				),
				true
			);
			?>
		</script>
		<?php
	}

	/**
	 * A single repeater row.
	 *
	 * @param int|string $index    Row index or template token.
	 * @param array      $item     Item data.
	 * @param bool       $template Whether this is the JS template.
	 */
	protected static function render_item_row( $index, $item, $template = false ) {
		$item     = wp_parse_args(
			$item,
			array(
				'kind'     => 'image',
				'image_id' => 0,
				'text'     => '',
				'alt'      => '',
				'link'     => '',
				'target'   => '',
				'color'    => '',
			)
		);
		$name     = 'omq_items[' . $index . ']';
		$thumb    = $item['image_id'] ? wp_get_attachment_image_url( $item['image_id'], 'thumbnail' ) : '';
		$has_img  = (bool) $thumb;
		?>
		<div class="omq-item-row" data-index="<?php echo esc_attr( $index ); ?>">
			<span class="omq-item-handle dashicons dashicons-menu"></span>

			<div class="omq-item-media <?php echo $has_img ? 'has-image' : ''; ?>">
				<img src="<?php echo esc_url( $thumb ? $thumb : OMQ_URL . 'assets/img/placeholder.svg' ); ?>" alt="" class="omq-item-thumb">
				<input type="hidden" class="omq-item-image-id" name="<?php echo esc_attr( $name ); ?>[image_id]" value="<?php echo esc_attr( $item['image_id'] ); ?>">
				<div class="omq-item-media-actions">
					<button type="button" class="button-link omq-pick-image"><?php esc_html_e( 'Select', 'ofnoa-marquee' ); ?></button>
					<button type="button" class="button-link omq-clear-image"><?php esc_html_e( 'Clear', 'ofnoa-marquee' ); ?></button>
				</div>
			</div>

			<div class="omq-item-fields">
				<label class="omq-mini">
					<span><?php esc_html_e( 'Type', 'ofnoa-marquee' ); ?></span>
					<select name="<?php echo esc_attr( $name ); ?>[kind]" class="omq-item-kind">
						<option value="image" <?php selected( $item['kind'], 'image' ); ?>><?php esc_html_e( 'Image', 'ofnoa-marquee' ); ?></option>
						<option value="text" <?php selected( $item['kind'], 'text' ); ?>><?php esc_html_e( 'Text', 'ofnoa-marquee' ); ?></option>
					</select>
				</label>

				<label class="omq-mini omq-mini--grow">
					<span><?php esc_html_e( 'Text / label', 'ofnoa-marquee' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[text]" value="<?php echo esc_attr( $item['text'] ); ?>" placeholder="<?php esc_attr_e( 'Brand name or ticker text', 'ofnoa-marquee' ); ?>">
				</label>

				<label class="omq-mini omq-mini--grow">
					<span><?php esc_html_e( 'Link', 'ofnoa-marquee' ); ?></span>
					<input type="url" name="<?php echo esc_attr( $name ); ?>[link]" value="<?php echo esc_attr( $item['link'] ); ?>" placeholder="https://">
				</label>

				<label class="omq-mini">
					<span><?php esc_html_e( 'Alt', 'ofnoa-marquee' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[alt]" value="<?php echo esc_attr( $item['alt'] ); ?>">
				</label>

				<label class="omq-mini">
					<span><?php esc_html_e( 'Color', 'ofnoa-marquee' ); ?></span>
					<input type="text" class="omq-color" name="<?php echo esc_attr( $name ); ?>[color]" value="<?php echo esc_attr( $item['color'] ); ?>" data-alpha-enabled="true">
				</label>

				<label class="omq-mini omq-mini--check">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[target]" value="_blank" <?php checked( $item['target'], '_blank' ); ?>>
					<span><?php esc_html_e( 'New tab', 'ofnoa-marquee' ); ?></span>
				</label>
			</div>

			<button type="button" class="omq-item-remove" title="<?php esc_attr_e( 'Remove', 'ofnoa-marquee' ); ?>">
				<span class="dashicons dashicons-trash"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Render one setting field.
	 *
	 * @param string $key      Field key.
	 * @param array  $def      Definition.
	 * @param array  $settings Current values.
	 */
	public static function render_field( $key, $def, $settings ) {
		$value     = isset( $settings[ $key ] ) ? $settings[ $key ] : ( isset( $def['default'] ) ? $def['default'] : '' );
		$condition = ! empty( $def['condition'] ) ? wp_json_encode( $def['condition'] ) : '';
		$classes   = array( 'omq-field', 'omq-field--' . $def['type'] );
		if ( in_array( $def['type'], array( 'textarea' ), true ) ) {
			$classes[] = 'omq-field--wide';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-key="<?php echo esc_attr( $key ); ?>"
			<?php if ( $condition ) : ?>data-condition="<?php echo esc_attr( $condition ); ?>"<?php endif; ?>>
			<label class="omq-field__label" for="omq-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $def['label'] ); ?></label>
			<div class="omq-field__control">
				<?php
				self::render_control( $key, $def, $value );

				if ( ! empty( $def['responsive'] ) ) {
					echo '<div class="omq-responsive">';
					foreach ( array(
						'tablet' => 'dashicons-tablet',
						'mobile' => 'dashicons-smartphone',
					) as $device => $icon ) {
						$rkey  = $key . '_' . $device;
						$rval  = isset( $settings[ $rkey ] ) ? $settings[ $rkey ] : '';
						printf(
							'<span class="omq-responsive__item"><span class="dashicons %1$s"></span><input type="number" name="omq_settings[%2$s]" value="%3$s" placeholder="%4$s" min="%5$s" max="%6$s" step="%7$s"></span>',
							esc_attr( $icon ),
							esc_attr( $rkey ),
							esc_attr( $rval ),
							esc_attr__( 'inherit', 'ofnoa-marquee' ),
							esc_attr( isset( $def['min'] ) ? $def['min'] : '' ),
							esc_attr( isset( $def['max'] ) ? $def['max'] : '' ),
							esc_attr( isset( $def['step'] ) ? $def['step'] : 1 )
						);
					}
					echo '</div>';
				}
				?>
			</div>
			<?php if ( ! empty( $def['desc'] ) ) : ?>
				<p class="omq-field__desc"><?php echo esc_html( $def['desc'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the actual input for a field.
	 *
	 * @param string $key   Field key.
	 * @param array  $def   Definition.
	 * @param mixed  $value Value.
	 */
	protected static function render_control( $key, $def, $value ) {
		$name = 'omq_settings[' . $key . ']';
		$id   = 'omq-' . $key;

		switch ( $def['type'] ) {

			case 'select':
				$options = OMQ_Fields::field_options( $def );
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="omq-input">';
				foreach ( $options as $opt_value => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $opt_value ),
						selected( (string) $value, (string) $opt_value, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				break;

			case 'toggle':
				printf(
					'<label class="omq-switch"><input type="hidden" name="%1$s" value="0"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s><span class="omq-switch__ui"></span></label>',
					esc_attr( $name ),
					esc_attr( $id ),
					checked( $value, 1, false )
				);
				break;

			case 'color':
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="omq-color" data-default-color="%4$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $def['default'] ) ? $def['default'] : '' )
				);
				break;

			case 'range':
				printf(
					'<span class="omq-range"><input type="range" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s" class="omq-input omq-range__input"><output class="omq-range__out">%3$s%7$s</output></span>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $def['min'] ) ? $def['min'] : 0 ),
					esc_attr( isset( $def['max'] ) ? $def['max'] : 100 ),
					esc_attr( isset( $def['step'] ) ? $def['step'] : 1 ),
					esc_html( isset( $def['unit'] ) ? $def['unit'] : '' )
				);
				break;

			case 'number':
				printf(
					'<span class="omq-number"><input type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s" class="omq-input"><em>%7$s</em></span>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $def['min'] ) ? $def['min'] : '' ),
					esc_attr( isset( $def['max'] ) ? $def['max'] : '' ),
					esc_attr( isset( $def['step'] ) ? $def['step'] : 1 ),
					esc_html( isset( $def['unit'] ) ? $def['unit'] : '' )
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="6" class="omq-input omq-textarea" placeholder="%4$s">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value ),
					esc_attr( isset( $def['placeholder'] ) ? $def['placeholder'] : '' )
				);
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="omq-input" placeholder="%4$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $def['placeholder'] ) ? $def['placeholder'] : '' )
				);
				break;
		}
	}

	/**
	 * Persist settings and items.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['omq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['omq_nonce'] ) ), 'omq_save_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw_settings = isset( $_POST['omq_settings'] ) ? wp_unslash( $_POST['omq_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$raw_items    = isset( $_POST['omq_items'] ) ? wp_unslash( $_POST['omq_items'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		update_post_meta( $post_id, '_omq_settings', OMQ_Fields::sanitize( (array) $raw_settings ) );
		update_post_meta( $post_id, '_omq_items', OMQ_Fields::sanitize_items( (array) $raw_items ) );
	}

	/**
	 * AJAX live preview.
	 */
	public static function ajax_preview() {
		check_ajax_referer( 'omq_preview', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'ofnoa-marquee' ) ), 403 );
		}

		$raw_settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$raw_items    = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$settings = OMQ_Fields::sanitize( (array) $raw_settings );
		$items    = OMQ_Render::collect_items( $settings, OMQ_Fields::sanitize_items( (array) $raw_items ) );

		$html = OMQ_Render::render( $settings, $items, 'omq-preview-' . wp_rand( 100, 9999 ) );

		wp_send_json_success( array( 'html' => $html ) );
	}
}
