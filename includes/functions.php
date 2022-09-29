<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

function mai_get_popup_defaults() {
	$defaults = apply_filters( 'mai_popup_default_args',
		[
			'id'        => '',
			'trigger'   => 'scroll', // 'scroll', 'timed', 'manual'.
			'style'     => 'modal', // 'modal', 'slideup'.
			'distance'  => '50', // percentage of scroll.
			'delay'     => '3', // time before showing when using 'timed' type. Uses float so it can be decimals.
			'width'     => '600px', // max-width of popup.
			'condition' => true, // bool or callable function to determine whether to display the popup. This could check for logged in, member, etc.
			'preview'   => false,
		]
	);

	return $defaults;
}
