<?php
/**
 * Front-end renderer: turns settings + items into markup and scoped CSS.
 *
 * @package Ofnoa_Marquee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class OMQ_Render
 */
class OMQ_Render {

	/**
	 * Auto-increment for unique ids.
	 *
	 * @var int
	 */
	protected static $seq = 0;

	/**
	 * Box shadow presets.
	 *
	 * @return array
	 */
	protected static function shadows() {
		return array(
			'none' => 'none',
			'sm'   => '0 2px 8px rgba(0,0,0,.08)',
			'md'   => '0 8px 24px rgba(0,0,0,.12)',
			'lg'   => '0 18px 48px rgba(0,0,0,.18)',
		);
	}

	/**
	 * Separator characters.
	 *
	 * @return array
	 */
	protected static function separators() {
		return array(
			'dot'  => '&bull;',
			'dash' => '&mdash;',
			'star' => '&#9733;',
		);
	}

	/**
	 * Whether the late (post-footer) asset fallback has already been printed.
	 *
	 * @var bool
	 */
	protected static $late_assets_done = false;

	/**
	 * Make sure the front-end CSS/JS are on the page, whatever renders us.
	 *
	 * Normally the handles are registered on wp_enqueue_scripts and this simply
	 * enqueues them. But page builders, REST previews and footer widgets can
	 * render a marquee outside that flow — in which case we register on the
	 * spot, and if even wp_footer has already run we print the tags inline so
	 * the marquee can never end up on a page without its assets.
	 *
	 * @return string Markup to prepend, empty in the normal case.
	 */
	public static function enqueue_assets() {
		if ( ! wp_style_is( 'omq-frontend', 'registered' ) ) {
			wp_register_style( 'omq-frontend', OMQ_URL . 'assets/css/omq-frontend.css', array(), OMQ_VERSION );
		}
		if ( ! wp_script_is( 'omq-frontend', 'registered' ) ) {
			wp_register_script( 'omq-frontend', OMQ_URL . 'assets/js/omq-frontend.js', array(), OMQ_VERSION, true );
		}

		wp_enqueue_style( 'omq-frontend' );
		wp_enqueue_script( 'omq-frontend' );

		// Too late for the normal queues — emit the tags ourselves, once.
		if ( did_action( 'wp_footer' ) && ! self::$late_assets_done ) {
			self::$late_assets_done = true;
			return '<link rel="stylesheet" href="' . esc_url( OMQ_URL . 'assets/css/omq-frontend.css?ver=' . OMQ_VERSION ) . '">'
				. '<script src="' . esc_url( OMQ_URL . 'assets/js/omq-frontend.js?ver=' . OMQ_VERSION ) . '"></script>';
		}

		return '';
	}

	/**
	 * Render a saved marquee by post ID.
	 *
	 * @param int   $post_id   Marquee post ID.
	 * @param array $overrides Optional setting overrides.
	 * @return string
	 */
	public static function render_post( $post_id, $overrides = array() ) {
		$post_id = absint( $post_id );
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || OMQ_CPT !== $post->post_type ) {
			return self::notice( __( 'Marquee not found.', 'ofnoa-marquee' ) );
		}
		$can_edit = current_user_can( 'edit_post', $post_id );

		if ( 'publish' !== $post->post_status && ! $can_edit ) {
			return '';
		}

		$warning = '';
		if ( 'publish' !== $post->post_status && $can_edit ) {
			$warning = self::notice(
				sprintf(
					/* translators: %s: marquee title */
					__( 'Heads up: the marquee "%s" is not published yet, so visitors will not see it. Only you (as an editor) see it here.', 'ofnoa-marquee' ),
					$post->post_title
				),
				'warning'
			);
		}

		$settings = OMQ_Fields::get_settings( $post_id );
		if ( ! empty( $overrides ) ) {
			$overrides = array_filter(
				$overrides,
				function ( $v ) {
					return '' !== $v && null !== $v;
				}
			);
			$settings  = OMQ_Fields::sanitize( array_merge( $settings, $overrides ) );
		}

		$items = self::collect_items( $settings, OMQ_Fields::get_items( $post_id ) );

		return $warning . self::render( $settings, $items, 'omq-' . $post_id . '-' . ( ++self::$seq ) );
	}

	/**
	 * Admin notice-ish inline message (only for editors).
	 *
	 * @param string $message Message.
	 * @return string
	 */
	protected static function notice( $message, $type = 'error' ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}
		$color = ( 'warning' === $type ) ? '#b45309' : '#dc2626';
		return '<div class="omq-notice omq-notice--' . esc_attr( $type ) . '" style="padding:10px;margin:0 0 8px;border:1px dashed ' . $color . ';color:' . $color . ';font:13px/1.4 sans-serif">'
			. esc_html( $message ) . '</div>';
	}

	/**
	 * Build the final item list (manual or dynamic).
	 *
	 * @param array $settings Settings.
	 * @param array $manual   Manual items.
	 * @return array
	 */
	public static function collect_items( $settings, $manual = array() ) {
		if ( 'posts' !== $settings['content_source'] ) {
			return is_array( $manual ) ? $manual : array();
		}

		$query = new WP_Query(
			array(
				'post_type'           => $settings['source_post_type'] ? $settings['source_post_type'] : 'post',
				'posts_per_page'      => (int) $settings['source_count'],
				'orderby'             => $settings['source_orderby'],
				'order'               => ( 'title' === $settings['source_orderby'] ) ? 'ASC' : 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'post_status'         => 'publish',
			)
		);

		$items = array();
		foreach ( $query->posts as $p ) {
			$items[] = array(
				'kind'     => ( 'logos' === $settings['display_mode'] ) ? 'image' : 'text',
				'image_id' => (int) get_post_thumbnail_id( $p ),
				'text'     => get_the_title( $p ),
				'alt'      => get_the_title( $p ),
				'link'     => $settings['source_link'] ? get_permalink( $p ) : '',
				'target'   => '',
				'color'    => '',
			);
		}
		wp_reset_postdata();

		return $items;
	}

	/**
	 * Main renderer.
	 *
	 * @param array  $settings Settings (already sanitized/merged).
	 * @param array  $items    Items.
	 * @param string $uid      Unique DOM id.
	 * @return string
	 */
	public static function render( $settings, $items, $uid = '' ) {
		$settings = wp_parse_args( is_array( $settings ) ? $settings : array(), OMQ_Fields::defaults() );
		$items    = is_array( $items ) ? $items : array();

		if ( empty( $items ) ) {
			return self::notice( __( 'This marquee has no items yet.', 'ofnoa-marquee' ) );
		}

		if ( ! $uid ) {
			$uid = 'omq-' . wp_rand( 1000, 999999 );
		}

		$late_assets = self::enqueue_assets();

		$rows      = max( 1, min( 3, (int) $settings['rows'] ) );
		$vertical  = in_array( $settings['direction'], array( 'up', 'down' ), true );
		$classes   = array(
			'omq',
			'omq--dir-' . $settings['direction'],
			'omq--mode-' . $settings['display_mode'],
			'omq--rows-' . $rows,
			$vertical ? 'omq--vertical' : 'omq--horizontal',
			'omq--width-' . $settings['width_mode'],
		);
		if ( $settings['fade_edges'] ) {
			$classes[] = 'omq--fade';
		}
		if ( $settings['pause_on_hover'] ) {
			$classes[] = 'omq--pause-hover';
		}
		if ( $settings['pause_on_click'] ) {
			$classes[] = 'omq--click-pause';
		}
		if ( $settings['fade_in'] ) {
			$classes[] = 'omq--fadein';
		}
		if ( $settings['reduced_motion'] ) {
			$classes[] = 'omq--rm';
		}
		if ( $settings['hide_desktop'] ) {
			$classes[] = 'omq--hide-d';
		}
		if ( $settings['hide_tablet'] ) {
			$classes[] = 'omq--hide-t';
		}
		if ( $settings['hide_mobile'] ) {
			$classes[] = 'omq--hide-m';
		}
		if ( $settings['extra_class'] ) {
			$classes[] = sanitize_html_class( str_replace( ' ', '-', $settings['extra_class'] ) );
		}

		$config = array(
			'pauseHover'  => (int) $settings['pause_on_hover'],
			'hoverSpeed'  => (int) $settings['hover_speed'],
			'clickPause'  => (int) $settings['pause_on_click'],
			'lazy'        => (int) $settings['lazy_start'],
			'reverseScroll' => (int) $settings['reverse_on_scroll'],
			'scrollBoost' => (int) $settings['scroll_boost'],
			'rows'        => $rows,
			'rowsMode'    => $settings['rows_mode'],
			'rowsSpeed'   => (int) $settings['rows_speed'],
			'rowsOffset'  => (int) $settings['rows_offset'],
			'vertical'    => $vertical ? 1 : 0,
			'direction'   => $settings['direction'],
		);

		$group_html = self::items_html( $items, $settings );

		ob_start();
		echo $late_assets; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::scoped_css( $uid, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<div id="<?php echo esc_attr( $uid ); ?>"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			style="<?php echo esc_attr( self::inline_vars( $settings ) ); ?>"
			data-omq="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
			role="marquee"
			<?php if ( $settings['aria_label'] ) : ?>
				aria-label="<?php echo esc_attr( $settings['aria_label'] ); ?>"
			<?php endif; ?>
		>
			<?php for ( $r = 1; $r <= $rows; $r++ ) : ?>
				<div class="omq__row omq__row--<?php echo (int) $r; ?><?php echo ( $r > 1 && 'opposite' === $settings['rows_mode'] && 0 === $r % 2 ) ? ' omq__row--rev' : ''; ?>">
					<div class="omq__viewport">
						<div class="omq__track">
							<div class="omq__group"><?php echo $group_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						</div>
					</div>
				</div>
			<?php endfor; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render all items of one group.
	 *
	 * @param array $items    Items.
	 * @param array $settings Settings.
	 * @return string
	 */
	protected static function items_html( $items, $settings ) {
		$html = '';
		$sep  = self::separator_html( $settings );

		foreach ( $items as $item ) {
			$html .= self::item_html( $item, $settings );
			$html .= $sep;
		}

		return $html;
	}

	/**
	 * Separator markup.
	 *
	 * @param array $settings Settings.
	 * @return string
	 */
	protected static function separator_html( $settings ) {
		$type = $settings['sep_type'];
		if ( 'none' === $type ) {
			return '';
		}
		if ( 'line' === $type ) {
			return '<span class="omq__sep omq__sep--line" aria-hidden="true"></span>';
		}
		$map  = self::separators();
		$char = isset( $map[ $type ] ) ? $map[ $type ] : esc_html( $settings['sep_char'] );
		return '<span class="omq__sep" aria-hidden="true">' . $char . '</span>';
	}

	/**
	 * Single item markup.
	 *
	 * @param array $item     Item.
	 * @param array $settings Settings.
	 * @return string
	 */
	protected static function item_html( $item, $settings ) {
		$item = wp_parse_args(
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

		$mode      = $settings['display_mode'];
		$show_img  = ( 'text' !== $mode ) && $item['image_id'];
		$show_text = ( 'logos' !== $mode || ! $show_img ) && '' !== $item['text'];

		$inner = '';

		if ( $show_img ) {
			$alt   = $item['alt'] ? $item['alt'] : $item['text'];
			$img   = wp_get_attachment_image(
				$item['image_id'],
				'medium_large',
				false,
				array(
					'class'   => 'omq__img',
					'alt'     => $alt,
					'loading' => 'lazy',
					'decoding' => 'async',
				)
			);
			$inner .= $img ? $img : '';
		}

		if ( $show_text ) {
			$style  = $item['color'] ? ' style="color:' . esc_attr( $item['color'] ) . '"' : '';
			$inner .= '<span class="omq__text"' . $style . '>' . esc_html( $item['text'] ) . '</span>';
		}

		if ( '' === trim( $inner ) ) {
			return '';
		}

		$classes = 'omq__item omq__item--' . ( $show_img ? 'logo' : 'text' );

		if ( $item['link'] ) {
			$target = $item['target'] ? $item['target'] : $settings['link_target'];
			$rel    = array();
			if ( '_blank' === $target ) {
				$rel[] = 'noopener';
				$rel[] = 'noreferrer';
			}
			if ( $settings['link_nofollow'] ) {
				$rel[] = 'nofollow';
			}
			return sprintf(
				'<a class="%1$s" href="%2$s" target="%3$s"%4$s>%5$s</a>',
				esc_attr( $classes ),
				esc_url( $item['link'] ),
				esc_attr( $target ),
				$rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '',
				$inner
			);
		}

		return '<div class="' . esc_attr( $classes ) . '">' . $inner . '</div>';
	}

	/**
	 * Build the inline CSS custom properties.
	 *
	 * @param array $settings Settings.
	 * @return string
	 */
	public static function inline_vars( $settings ) {
		$shadows = self::shadows();
		$vars    = array();

		$vars['--omq-gap']       = (int) $settings['gap'] . 'px';
		$vars['--omq-rows-gap']  = (int) $settings['rows_gap'] . 'px';
		$vars['--omq-pad-y']     = (int) $settings['padding_y'] . 'px';
		$vars['--omq-pad-x']     = (int) $settings['padding_x'] . 'px';
		$vars['--omq-margin-y']  = (int) $settings['margin_y'] . 'px';
		$vars['--omq-maxw']      = ( 'boxed' === $settings['width_mode'] ) ? (int) $settings['max_width'] . 'px' : '100%';
		$vars['--omq-vheight']   = (int) $settings['vertical_height'] . 'px';
		$vars['--omq-align']     = $settings['align'];

		// Background.
		if ( 'solid' === $settings['bg_type'] && $settings['bg_color'] ) {
			$vars['--omq-bg'] = $settings['bg_color'];
		} elseif ( 'gradient' === $settings['bg_type'] ) {
			$vars['--omq-bg'] = sprintf(
				'linear-gradient(%1$ddeg, %2$s 0%%, %3$s 100%%)',
				(int) $settings['grad_angle'],
				$settings['grad_from'] ? $settings['grad_from'] : '#4f46e5',
				$settings['grad_to'] ? $settings['grad_to'] : '#06b6d4'
			);
		} else {
			$vars['--omq-bg'] = 'transparent';
		}

		$vars['--omq-bw']     = (int) $settings['border_width'] . 'px';
		$vars['--omq-bs']     = $settings['border_style'];
		$vars['--omq-bc']     = $settings['border_color'] ? $settings['border_color'] : 'transparent';
		$vars['--omq-radius'] = (int) $settings['radius'] . 'px';

		$shadow = 'custom' === $settings['shadow']
			? $settings['shadow_custom']
			: ( isset( $shadows[ $settings['shadow'] ] ) ? $shadows[ $settings['shadow'] ] : 'none' );
		$vars['--omq-shadow'] = $shadow ? $shadow : 'none';

		$vars['--omq-fade'] = (int) $settings['fade_width'] . 'px';

		// Logos.
		$vars['--omq-logo-h']    = (int) $settings['logo_height'] . 'px';
		$vars['--omq-logo-maxw'] = $settings['logo_max_width'] ? (int) $settings['logo_max_width'] . 'px' : 'none';
		$vars['--omq-logo-fit']  = $settings['logo_fit'];
		$vars['--omq-gray']      = (int) $settings['logo_grayscale'] . '%';
		$vars['--omq-op']        = round( (int) $settings['logo_opacity'] / 100, 3 );
		$vars['--omq-blend']     = $settings['logo_blend'];
		$vars['--omq-h-gray']    = (int) $settings['hover_grayscale'] . '%';
		$vars['--omq-h-op']      = round( (int) $settings['hover_opacity'] / 100, 3 );
		$vars['--omq-h-scale']   = round( (int) $settings['hover_scale'] / 100, 3 );

		// Items.
		$vars['--omq-item-bg']     = $settings['item_bg'] ? $settings['item_bg'] : 'transparent';
		$vars['--omq-item-bg-h']   = $settings['item_bg_hover'] ? $settings['item_bg_hover'] : 'var(--omq-item-bg)';
		$vars['--omq-item-radius'] = (int) $settings['item_radius'] . 'px';
		$vars['--omq-item-bw']     = (int) $settings['item_border_w'] . 'px';
		$vars['--omq-item-bc']     = $settings['item_border_c'] ? $settings['item_border_c'] : 'transparent';
		$vars['--omq-item-px']     = (int) $settings['item_pad_x'] . 'px';
		$vars['--omq-item-py']     = (int) $settings['item_pad_y'] . 'px';
		$vars['--omq-item-shadow'] = isset( $shadows[ $settings['item_shadow'] ] ) ? $shadows[ $settings['item_shadow'] ] : 'none';
		$vars['--omq-trans']       = (int) $settings['transition_ms'] . 'ms';

		// Typography.
		if ( $settings['font_family'] ) {
			$vars['--omq-ff'] = $settings['font_family'];
		}
		$vars['--omq-fs']      = (int) $settings['font_size'] . 'px';
		$vars['--omq-fw']      = $settings['font_weight'] ? $settings['font_weight'] : 'inherit';
		$vars['--omq-lh']      = (float) $settings['line_height'];
		$vars['--omq-ls']      = (float) $settings['letter_spacing'] . 'px';
		$vars['--omq-tt']      = $settings['text_transform'];
		$vars['--omq-color']   = $settings['text_color'] ? $settings['text_color'] : 'inherit';
		$vars['--omq-color-h'] = $settings['text_hover_color'] ? $settings['text_hover_color'] : 'var(--omq-color)';
		if ( $settings['text_shadow'] ) {
			$vars['--omq-tshadow'] = $settings['text_shadow'];
		}

		// Separator.
		$vars['--omq-sep-color'] = $settings['sep_color'] ? $settings['sep_color'] : 'currentColor';
		$vars['--omq-sep-size']  = (int) $settings['sep_size'] . 'px';

		// Animation.
		$vars['--omq-speed']  = (int) $settings['speed'];
		$vars['--omq-timing'] = $settings['timing'];
		$vars['--omq-delay']  = (int) $settings['start_delay'] . 'ms';

		if ( (int) $settings['z_index'] > 0 ) {
			$vars['--omq-z'] = (int) $settings['z_index'];
		}

		$out = '';
		foreach ( $vars as $key => $value ) {
			$out .= $key . ':' . $value . ';';
		}
		return $out;
	}

	/**
	 * Per-instance CSS: responsive overrides + custom CSS.
	 *
	 * @param string $uid      Element id.
	 * @param array  $settings Settings.
	 * @return string
	 */
	public static function scoped_css( $uid, $settings ) {
		$sel    = '#' . $uid;
		$tablet = (int) $settings['tablet_bp'];
		$mobile = (int) $settings['mobile_bp'];
		$css    = '';

		$map = array(
			'gap'         => array( '--omq-gap', 'px' ),
			'logo_height' => array( '--omq-logo-h', 'px' ),
			'item_pad_x'  => array( '--omq-item-px', 'px' ),
			'font_size'   => array( '--omq-fs', 'px' ),
			'speed'       => array( '--omq-speed', '' ),
		);

		foreach ( array( 'tablet' => $tablet, 'mobile' => $mobile ) as $device => $bp ) {
			$rules = '';
			foreach ( $map as $key => $info ) {
				$rkey = $key . '_' . $device;
				if ( isset( $settings[ $rkey ] ) && '' !== $settings[ $rkey ] && null !== $settings[ $rkey ] ) {
					$rules .= $info[0] . ':' . (int) $settings[ $rkey ] . $info[1] . ';';
				}
			}
			if ( $rules ) {
				$css .= '@media(max-width:' . $bp . 'px){' . $sel . '{' . $rules . '}}';
			}
		}

		// Per-device visibility, using this instance's own breakpoints.
		if ( ! empty( $settings['hide_desktop'] ) ) {
			$css .= '@media(min-width:' . ( $tablet + 1 ) . 'px){' . $sel . '{display:none !important}}';
		}
		if ( ! empty( $settings['hide_tablet'] ) ) {
			$css .= '@media(max-width:' . $tablet . 'px) and (min-width:' . ( $mobile + 1 ) . 'px){' . $sel . '{display:none !important}}';
		}
		if ( ! empty( $settings['hide_mobile'] ) ) {
			$css .= '@media(max-width:' . $mobile . 'px){' . $sel . '{display:none !important}}';
		}

		if ( ! empty( $settings['custom_css'] ) ) {
			$css .= str_replace( '{{WRAPPER}}', $sel, $settings['custom_css'] );
		}

		if ( ! $css ) {
			return '';
		}

		return '<style id="' . esc_attr( $uid ) . '-css">' . wp_strip_all_tags( $css ) . '</style>';
	}
}
