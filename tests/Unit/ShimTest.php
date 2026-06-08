<?php

beforeEach( function () {
    maipopups_stub_wp_helpers();
    if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', '/tmp/' ); }
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
} );

test( 'maipopups_get_defaults returns the defaults with the filter applied', function () {
    $defaults = maipopups_get_defaults();
    expect( $defaults )->toHaveKey( 'trigger' );
    expect( $defaults['trigger'] )->toBe( 'manual' );
} );
