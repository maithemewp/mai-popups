<?php

use Brain\Monkey\Functions;

function maipopups_stub_wp_helpers(): void {
    foreach ( [ 'sanitize_text_field', 'sanitize_key', '__' ] as $fn ) {
        Functions\when( $fn )->returnArg( 1 );
    }
    // Faithful escaping stubs so a missing escape at the output sink is caught.
    // WP's esc_attr/esc_html encode & < > " ' — htmlspecialchars(ENT_QUOTES) matches.
    foreach ( [ 'esc_attr', 'esc_html' ] as $fn ) {
        Functions\when( $fn )->alias( fn ( $arg ) => htmlspecialchars( (string) $arg, ENT_QUOTES ) );
    }
    Functions\when( 'rest_sanitize_boolean' )->alias( function ( $value ) {
        if ( is_string( $value ) ) {
            $value = strtolower( $value );
            if ( in_array( $value, [ 'false', '0', '', 'no', 'off' ], true ) ) { return false; }
        }
        return (bool) $value;
    } );
    Functions\when( 'shortcode_atts' )->alias(
        fn ( $defaults, $atts ) => array_merge( $defaults, array_intersect_key( (array) $atts, $defaults ) )
    );
    Functions\when( 'apply_filters' )->returnArg( 2 );
}

/**
 * Asserts that $actual matches a stored golden snapshot.
 *
 * On first run (no snapshot file yet) the baseline is recorded and the
 * assertion trivially passes. On subsequent runs the actual output is
 * compared byte-for-byte against the recorded baseline.
 *
 * Reused by later tasks to prove the new Renderer produces byte-identical
 * output to the legacy Mai_Popup class.
 */
function maipopups_assert_golden( string $name, string $actual ): void {
    $dir = __DIR__ . '/__snapshots__';

    if ( ! is_dir( $dir ) ) {
        mkdir( $dir, 0777, true );
    }

    $file = "$dir/$name.html";

    // First run records the baseline.
    if ( ! file_exists( $file ) ) {
        file_put_contents( $file, $actual );
    }

    expect( $actual )->toBe( file_get_contents( $file ) );
}
