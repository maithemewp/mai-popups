# Mai Popups
A lightweight and flexible popup, slideup, notice, and hello bar block. Works best with Mai Theme, but should work in all block-based themes. Requires ACF Pro v6+.

## Usage
### Mai Popup block
Add Mai Popup block to any post/page and configure the settings. Create your popup content using any blocks.

### Helper function
Developers can add any popup via `mai_do_popup( $args, $content )` function. Add this function anywhere and it will automatically add the popup to the footer.

```
mai_do_popup( $args, $content );
```

**$args**

```
$args = [
	'id'        => '', // The HTML id when trigger is manual. Must start with `mai-popup-`.
	'trigger'   => 'manual', // The popup trigger. Accepts 'scroll', 'timed', 'load', and 'manual'.
	'animate'   => 'fade', // The type of animation. Accepts 'fade', 'up', and 'down'.
	'distance'  => '50', // The percentage distance of scroll before triggering popup when the trigger is 'scroll'.
	'delay'     => '3', // The time in seconds before displaying the popup when using 'timed' type. Uses float so it can be decimals.
	'position'  => 'center center', // The position of popup, with space-separated values. First value is vertical, second value is horizontal. Accepts 'start', 'center', and 'end'.
	'width'     => '', // The max-width of the popup. Accepts any CSS value.
	'repeat'    => '7 days', // The time before showing the popup to the same user. Sets a cookie with the expiration time. Accepts any value that `strtotime()` accepts.
	'condition' => true, // A bool value or callable function to determine whether to display the popup. This could check for logged in, member, etc.
];
```

**$content**

Any HTML string.
