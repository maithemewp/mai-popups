<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Do a new popup.
 *
 * @since 0.2.0
 *
 * @param array  $args    The popup args.
 * @param string $content The popup content.
 *
 * @return void
 */
function mai_do_popup( $args, $content = '' ) {
	$popup = new Mai_Popup( $args, $content );
	$popup->render();
}

/**
 * Gets defauilt args.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maipopups_get_defaults() {
	$defaults = apply_filters( 'mai_popup_default_args',
		[
			'id'        => '', // The HTML id when trigger is manual. Must start with `mai-popup-`.
			'class'     => '', // Additional HTML classes.
			'trigger'   => 'manual', // The popup trigger. Accepts 'scroll', 'timed', 'load', and 'manual'.
			'animate'   => 'fade', // The type of animation. Accepts 'fade', 'up', and 'down'.
			'distance'  => '50', // The percentage distance of scroll before triggering popup when the trigger is 'scroll'.
			'delay'     => '3', // The time in seconds before displaying the popup when using 'timed' type. Uses float so it can be decimals.
			'position'  => 'center center', // The position of popup, with space-separated values. First value is vertical, second value is horizontal. Accepts 'start', 'center', and 'end'.
			'width'     => '', // The max-width of the popup. Accepts any CSS value.
			'repeat'    => '7 days', // The time before showing the popup to the same user. Sets a cookie with the expiration time. Accepts any value that `strtotime()` accepts.
			'condition' => true, // A bool value or callable function to determine whether to display the popup. This could check for logged in, member, etc.
			'preview'   => false, // If viewing in editor or not.
		]
	);

	return $defaults;
}
