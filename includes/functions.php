<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

function maipopups_get_defaults() {
	$defaults = apply_filters( 'mai_popup_default_args',
		[
			'id'        => '',
			'trigger'   => 'scroll', // 'scroll', 'timed', 'load', 'manual'.
			'animate'   => 'fade', // 'fade', 'up', 'down'.
			'distance'  => '50', // percentage of scroll.
			'delay'     => '3', // time before showing when using 'timed' type. Uses float so it can be decimals.
			'position'  => 'center', // position of popup.
			'width'     => '600px', // max-width of popup.
			'overlay'   => true, // show overlay.
			'repeat'    => '7 days', // time before showing the popu to the same user.
			'condition' => true, // bool or callable function to determine whether to display the popup. This could check for logged in, member, etc.
			'preview'   => false,
		]
	);

	return $defaults;
}
