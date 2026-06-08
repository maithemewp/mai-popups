<?php

use Brain\Monkey\Functions;

function maipopups_stub_wp_helpers(): void {
    foreach ( [ 'esc_attr', 'esc_html', 'sanitize_text_field', 'sanitize_key', '__' ] as $fn ) {
        Functions\when( $fn )->returnArg( 1 );
    }
    Functions\when( 'rest_sanitize_boolean' )->alias( fn ( $v ) => filter_var( $v, FILTER_VALIDATE_BOOLEAN ) );
    Functions\when( 'shortcode_atts' )->alias(
        fn ( $defaults, $atts ) => array_merge( $defaults, array_intersect_key( (array) $atts, $defaults ) )
    );
    Functions\when( 'apply_filters' )->returnArg( 2 );
}
