=== Ofnoa Marquee — Logo & Text Ticker ===
Contributors: lirish1973
Tags: marquee, ticker, logo carousel, news ticker, scrolling text
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional, fully customizable marquee for logos and text — 4 directions, multi-row, per-device settings, Gutenberg block, Elementor widget and shortcode.

== Description ==

Ofnoa Marquee turns logos and text into a smooth, seamless scrolling strip with real design
control: colors, sizes, speeds, directions, single or double tickers, edge fade, hover
effects and per-device values.

Features:

* Unlimited marquees, each with its own settings
* Logos, text ticker, or a mix of both
* Four directions and up to three rows running in opposite directions
* Speed in pixels per second — constant no matter how many items
* Seamless infinite loop with automatic cloning and measuring
* Gradient or solid backgrounds, borders, radius, shadows, edge fade
* Grayscale, opacity, blend mode and hover effects for logos
* Full typography control and five separator styles
* Tablet and mobile values for gap, speed, logo height, font size and padding
* Dynamic content from any post type
* Live preview in the editor
* Shortcode, Gutenberg block, Elementor widget, classic widget, PHP function
* Import / export as JSON
* Accessible: reduced-motion support, focus handling, hidden clones
* Automatic updates from GitHub

== Installation ==

1. Upload the plugin ZIP through Plugins → Add New → Upload Plugin.
2. Activate it.
3. Go to Marquee → Add New, add items, style it, publish.
4. Copy the shortcode, use the block, or drop in the Elementor widget.

== Frequently Asked Questions ==

= Does it work with RTL sites? =
Yes. Text direction is detected per item, so Hebrew and Arabic render correctly while the
animation keeps a consistent flow.

= Will adding logos slow the marquee down? =
No. Speed is defined in pixels per second, so more items means a longer loop at the same
visual speed.

= Does it use jQuery? =
Not on the front end — the engine is vanilla JavaScript and the animation itself is pure CSS.

== Changelog ==

= 1.0.2 =
* Fixed: on themes that force `img { width:100%; height:auto }`, logos rendered at full
  natural size and the marquee appeared frozen. Image geometry is now pinned.
* Fixed: fade-in could leave a marquee invisible where CSS keyframes never run.
* Added: console warning when a theme rule blows up the measured row width.

= 1.0.1 =
* Fixed: draft marquees were hidden from the Elementor / block / widget pickers, which
  could leave a widget with nothing selected and no output on the page.
* Added: clear editor notices in Elementor, and a front-end warning for editors when a
  placed marquee is not published.
* Hardened front-end asset loading for page builders and footer widgets.

= 1.0.0 =
* Initial release.
