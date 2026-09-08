# Ofnoa Marquee — Logo & Text Ticker

A professional, fully customizable marquee / ticker plugin for WordPress: scrolling logo
walls, news tickers, announcement bars — with real design control and no jQuery on the
front end.

> Repository: `lirish1973/ofnoa-marquee` · License: GPL-2.0-or-later · Requires WP 5.8 · PHP 7.2

---

## Highlights

| | |
|---|---|
| **Unlimited marquees** | Every marquee is its own post — build as many as you need, duplicate any of them in one click. |
| **Logos, text or both** | Image items, text items, or an image with a label next to it. |
| **4 directions** | Right‑to‑left, left‑to‑right, bottom‑to‑top, top‑to‑bottom. |
| **1 / 2 / 3 rows** | Extra rows can run in the opposite direction with their own speed and start offset. |
| **Constant speed** | Speed is set in **pixels per second**, so adding items never changes how fast it moves. |
| **Seamless loop** | The content group is measured and cloned just enough times to fill the viewport — no gaps, no jumps. |
| **Live preview** | See the result while you edit, at desktop / tablet / mobile widths. |
| **Per‑device settings** | Gap, speed, logo height, font size and item padding each have tablet and mobile values. |
| **Dynamic content** | Pull the latest posts (any post type) instead of typing items manually. |
| **Accessible** | Real `prefers-reduced-motion` support, focus styles, cloned content hidden from screen readers, keyboard focus pauses the animation. |
| **Fast** | Pure CSS animation on the GPU, ~9 KB of vanilla JS, assets loaded only on pages that actually render a marquee. |

---

## Installation

### From a release (recommended)

1. Download `ofnoa-marquee.zip` from the [latest release](https://github.com/lirish1973/ofnoa-marquee/releases/latest).
2. WordPress → **Plugins → Add New → Upload Plugin**.
3. Activate. A new **Marquee** menu appears in the sidebar.

### From source

```bash
cd wp-content/plugins
git clone https://github.com/lirish1973/ofnoa-marquee.git ofnoa-marquee
```

### Updates

The plugin checks GitHub releases every 6 hours and offers updates through the normal
WordPress update screen. You can force a check under **Marquee → Tools → Check for updates
now**, disable checks entirely, or add a token for a private repository.

---

## Usage

### Shortcode

```
[ofnoa_marquee id="123"]
```

Any setting can be overridden inline:

```
[ofnoa_marquee id="123" speed="120" direction="right" rows="2" logo_height="80" fade_edges="1"]
```

`[omq id="123"]` is a shorter alias.

### Gutenberg

Add the **Ofnoa Marquee** block and pick a marquee. Speed and direction can be overridden
per block; the block renders server-side so the editor shows the real thing.

### Elementor

Search for **Ofnoa Marquee** in the General category. The widget exposes the marquee
selector plus overrides for direction, rows, speed, gap, logo height, font size, colors,
background, edge fade and pause on hover.

### PHP

```php
<?php ofnoa_marquee( 123 ); ?>

<?php echo ofnoa_get_marquee( 123, array( 'direction' => 'right', 'speed' => 90 ) ); ?>
```

### Classic widget

**Appearance → Widgets → Ofnoa Marquee.**

---

## Settings reference

**Content** — marquee type (logos / text / mixed), manual items or dynamic post query
(post type, count, order, link to post).

**Layout** — direction, rows, extra-row direction / speed / offset / gap, item gap,
alignment, full or boxed width, max width, vertical height, padding, margin.

**Container style** — transparent / solid / gradient background with angle, border width,
style, color, radius, shadow presets or a custom shadow, edge fade with size.

**Items style** — logo height, max width, object-fit, grayscale, opacity, blend mode,
hover grayscale / opacity / scale, item background (normal + hover), radius, border,
padding, shadow, transition duration.

**Text & separator** — font family, size, weight, line height, letter spacing, transform,
color, hover color, text shadow; separator style (dot, dash, star, line, custom character)
with its own color and size.

**Animation** — speed in px/s, timing function, pause on hover, hover speed, click to
pause, start delay, reverse on scroll, scroll speed boost, animate only when visible, fade
in, respect reduced motion.

**Advanced** — link target, `rel="nofollow"`, ARIA label, extra CSS class, hide on
desktop / tablet / mobile, custom breakpoints, `z-index`, custom CSS with a `{{WRAPPER}}`
placeholder.

---

## Custom CSS

Inside a marquee's **Advanced → Custom CSS**, `{{WRAPPER}}` is replaced with that
instance's ID:

```css
{{WRAPPER}} .omq__item--logo { padding: 0 8px; }
{{WRAPPER}} .omq__text { text-shadow: 0 2px 8px rgba(0,0,0,.2); }
```

## Markup & hooks

```html
<div id="omq-123-1" class="omq omq--dir-left …" style="--omq-gap:40px; …">
  <div class="omq__row omq__row--1">
    <div class="omq__viewport">
      <div class="omq__track">
        <div class="omq__group"><!-- items + separators --></div>
      </div>
    </div>
  </div>
</div>
```

Every visual value is a CSS custom property (`--omq-gap`, `--omq-logo-h`, `--omq-color`,
`--omq-speed`, …), so a child theme can restyle a marquee without touching the plugin.

JavaScript API:

```js
window.OfnoaMarquee.initAll( container );  // init marquees added dynamically
window.OfnoaMarquee.refreshAll();          // re-measure after a layout change
window.OfnoaMarquee.destroy( element );    // tear one down
```

PHP filter:

```php
add_filter( 'omq_field_schema', function ( $schema ) {
    $schema['animation']['fields']['speed']['default'] = 100;
    return $schema;
} );
```

---

## Development

```
ofnoa-marquee.php            Bootstrap, constants, asset registration
includes/
  class-omq-fields.php       Field schema — defaults, admin UI and sanitization in one place
  class-omq-post-type.php    CPT, admin columns, duplication
  class-omq-metabox.php      Builder UI, item repeater, AJAX live preview, saving
  class-omq-render.php       HTML + scoped CSS generation
  class-omq-shortcode.php    Shortcode and template tags
  class-omq-block.php        Gutenberg block (server rendered)
  class-omq-elementor*.php   Elementor widget
  class-omq-widget.php       Classic widget
  class-omq-tools.php        Options, import / export
  class-omq-updater.php      GitHub release updater
assets/css|js|img            Front-end and admin assets
```

Adding a setting means adding one entry to `OMQ_Fields::schema()` — the admin control,
default value, sanitizer and shortcode attribute all follow automatically. Render it by
mapping the key to a CSS variable in `OMQ_Render::inline_vars()`.

### Releasing

```bash
# bump the version in ofnoa-marquee.php, readme.txt and CHANGELOG.md first
git tag v1.0.1
git push origin v1.0.1
```

The GitHub Action builds `ofnoa-marquee.zip` (correctly nested in an `ofnoa-marquee/`
folder) and publishes it as a release asset — which is exactly what the in-plugin updater
downloads.

---

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
