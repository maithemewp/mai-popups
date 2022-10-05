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
	$popup->do();
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
			'id'        => '',
			'trigger'   => 'manual', // 'scroll', 'timed', 'load', 'manual'.
			'animate'   => 'fade', // 'fade', 'up', 'down'.
			'distance'  => '50', // percentage of scroll.
			'delay'     => '3', // time before showing when using 'timed' type. Uses float so it can be decimals.
			'position'  => 'center center', // position of popup.
			'width'     => '', // max-width of popup.
			'repeat'    => '7 days', // time before showing the popu to the same user.
			'condition' => true, // bool or callable function to determine whether to display the popup. This could check for logged in, member, etc.
			'preview'   => false,
		]
	);

	return $defaults;
}
