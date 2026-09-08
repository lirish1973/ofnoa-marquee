# Changelog

All notable changes to Ofnoa Marquee are documented here.
This project follows [Semantic Versioning](https://semver.org/).

## [1.0.2] - 2026-09-08

### Fixed
- **Logos rendered at their full natural size on some themes.** Themes and page
  builders commonly ship `img { width: 100%; height: auto }` at a higher
  specificity than a single class. Inside a track sized with `max-content` that
  becomes circular — the image fills a track that is sized by the image — so a
  60px logo rendered at ~3000px, the marquee grew several screens tall and the
  loop stretched to ~24 minutes, which looks exactly like "nothing happens".
  The geometry the marquee depends on (image height, width, fit, item flex,
  nowrap) is now pinned, while still reading from the CSS variables, so
  `{{WRAPPER}} { --omq-logo-h: 80px; }` remains the way to restyle it.
- Fade-in no longer depends solely on a CSS animation: the JS "built" class also
  reveals the marquee, so it can't stay invisible where keyframes never tick.
- A row measuring over 50,000px now logs a console warning naming the likely
  theme rule, instead of silently animating something absurd.

## [1.0.1] - 2026-09-08

### Fixed
- Marquees saved as **draft** were missing from the Elementor, Gutenberg and classic
  widget pickers, so a widget could end up with nothing selected and render silently
  empty. All pickers now list drafts, pending and private marquees and label their status.
- The Elementor widget now explains itself in the editor instead of rendering nothing:
  no marquee selected, no marquees created yet, selected marquee deleted, or a marquee
  that produces no output.
- Editors now see a warning on the front end when a placed marquee is not published,
  instead of it silently disappearing for visitors.
- Front-end CSS/JS are registered on demand and, if a marquee renders after `wp_footer`
  (footer widgets, some builder flows), the assets are emitted inline — a marquee can no
  longer land on a page without its stylesheet or script.

## [1.0.0] - 2026-09-08

### Added
- Unlimited marquees managed as a custom post type, with duplicate support.
- Logo, text and mixed modes; manual items or dynamic items pulled from any post type.
- Four directions (left, right, up, down) plus single / double / triple row tickers with
  opposite direction, independent speed and start offset.
- Constant px-per-second speed that never changes when items are added.
- Full styling control: background (solid / gradient), borders, radius, shadows, edge fade
  mask, item background, padding, hover scale, grayscale, opacity, blend mode.
- Typography controls, per-item color, and five separator styles.
- Per-device values (desktop / tablet / mobile) for gap, speed, logo height, font size and
  item padding, plus per-device visibility and custom breakpoints.
- Pause on hover, hover speed, click to pause, start delay, reverse on scroll, scroll speed
  boost, animate-only-when-visible and reduced-motion support.
- Live preview inside the editor with desktop / tablet / mobile widths.
- Shortcode with attribute overrides, Gutenberg block, Elementor widget, classic widget and
  PHP template tags.
- JSON import / export and a tools screen.
- Automatic updates from GitHub releases.
