# Mai Popups

A lightweight and flexible popup, slide-up, notice, and hello-bar block, built on the native `<dialog>` element. Works best with Mai Theme, but should work in any block-based theme.

Centered popups render as accessible modals (native focus trap, inert background, `Esc` to close, and a dimmed `::backdrop`). Positioned popups (top/bottom/corner) render as non-modal bars and slide-ins.

## Requirements

- WordPress 6.9+
- PHP 8.2+
- ACF Pro 6+

## Usage

### Mai Popup block

Add the **Mai Popup** block to any post or page and build the popup content with any blocks. Configure behavior (trigger, animation, position, width, padding, repeat, colors, etc.) in the block settings sidebar.

### Open a popup from a link

Give the popup an **id** (it must start with `mai-popup-`, e.g. `mai-popup-newsletter`) and link to it from anywhere:

```html
<a href="#mai-popup-newsletter">Open the newsletter popup</a>
```

Any link whose `href` starts with `#mai-popup-` opens the matching popup. Manually opening a popup this way never sets the repeat cookie, so it always works.

### Disable closing

The "Disable closing" setting removes the close button and disables click-outside-to-close. To let users close it, place a link or button with the class `mai-popup-close` inside the popup:

```html
<a class="mai-popup-close" href="#">No thanks</a>
```

### Helper function

Developers can render a popup anywhere with `mai_do_popup( $args, $content )`. It automatically prints the popup in the footer.

```php
mai_do_popup( $args, $content );
```

**`$args`**

```php
$args = [
    'id'            => '',              // HTML id; also the manual-open anchor target. Must start with `mai-popup-`.
    'class'         => '',              // Extra CSS class(es) on the popup.
    'trigger'       => 'manual',        // 'manual', 'load', 'scroll', or 'time'.
    'animate'       => 'fade',          // 'fade', 'up', or 'down'.
    'distance'      => '50',            // Scroll percentage before triggering (trigger = 'scroll').
    'delay'         => '3',             // Seconds before showing (trigger = 'time'). Accepts decimals.
    'position'      => 'center center', // "vertical horizontal", each 'start'|'center'|'end'. "center center" = modal.
    'width'         => '',              // Max-width. Any CSS value; a bare number is treated as px.
    'padding'       => 'xl',            // '', 'sm', 'md', 'lg', 'xl', 'xxl', or 'xxxl'.
    'repeat'        => '7 days',        // How long before showing again to the same user (sets a cookie).
                                        // Accepts anything strtotime() understands ("2 weeks") or a plain
                                        // number of days. Use 0 to always show.
    'repeat_roles'  => [],              // Role slugs that bypass the repeat cookie (always see the popup).
    'disable_close' => false,           // Remove the close button + click-outside close. Use .mai-popup-close to close.
    'background'    => '',              // Background color slug (theme palette).
    'color'         => '',              // Text color slug (theme palette).
    'condition'     => true,            // Bool or callable; return false to suppress (e.g. logged-in checks).
];
```

**`$content`**

Any HTML string — the popup's inner content.

### Defaults filter

All defaults above can be filtered:

```php
/**
 * Change the default repeat duration for all popups.
 *
 * @param array $defaults The default popup args.
 *
 * @return array
 */
add_filter( 'mai_popup_default_args', function( $defaults ) {
    $defaults['repeat'] = '30 days';

    return $defaults;
} );
```

## Development

Built with `@wordpress/scripts`. Source lives in `src/` and is compiled to `build/`.

```bash
npm install
npm run build      # production build
npm run start      # watch mode

composer install
composer test      # Pest unit tests
composer stan      # PHPStan static analysis
```
