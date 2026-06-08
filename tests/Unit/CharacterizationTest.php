<?php

use Brain\Monkey\Functions;

beforeEach( function () {
    // Stub the shared WP helper functions (esc_*, sanitize_*, shortcode_atts, etc.).
    maipopups_stub_wp_helpers();

    // Logged-out, no caps: keeps use_cookie() deterministic.
    Functions\when( 'is_user_logged_in' )->justReturn( false );
    Functions\when( 'current_user_can' )->justReturn( false );

    // The legacy class guards on ABSPATH and references plugin constants in
    // get_scripts_styles(). Define them once, pointing at the REAL plugin so
    // filemtime() resolves against the actual asset files.
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', '/tmp/' );
    }
    if ( ! defined( 'MAI_POPUPS_VERSION' ) ) {
        define( 'MAI_POPUPS_VERSION', '0.0.0-test' );
    }
    if ( ! defined( 'MAI_POPUPS_PLUGIN_DIR' ) ) {
        define( 'MAI_POPUPS_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
    }
    if ( ! defined( 'MAI_POPUPS_PLUGIN_URL' ) ) {
        define( 'MAI_POPUPS_PLUGIN_URL', 'http://example.test/wp-content/plugins/mai-popups/' );
    }

    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/classes/class-popup.php';
} );

dataset( 'popup_configs', [
    'modal-time' => [ [
        '_name'    => 'modal-time',
        'trigger'  => 'time',
        'delay'    => '3',
        'position' => 'center center',
        'animate'  => 'fade',
        'width'    => '600px',
        'padding'  => 'md',
    ] ],
    'modal-scroll' => [ [
        '_name'    => 'modal-scroll',
        'trigger'  => 'scroll',
        'distance' => '50',
        'position' => 'center center',
        'animate'  => 'up',
    ] ],
    'bar-bottom-load' => [ [
        '_name'    => 'bar-bottom-load',
        'trigger'  => 'load',
        'position' => 'end center',
        'animate'  => 'up',
        'width'    => '100%',
    ] ],
    'corner-manual' => [ [
        '_name'         => 'corner-manual',
        'trigger'       => 'manual',
        'position'      => 'end end',
        'id'            => 'mai-popup-fixed',
        'disable_close' => true,
    ] ],
    'colored-padding' => [ [
        '_name'      => 'colored-padding',
        'trigger'    => 'time',
        'repeat'     => '7 days',
        'background' => 'primary',
        'color'      => 'white',
        'padding'    => 'lg',
        'class'      => 'my-popup',
    ] ],
] );

test( 'legacy Mai_Popup markup is locked', function ( array $args ) {
    // Derive the stable snapshot name from the case, then remove it so it
    // never leaks into the real popup args.
    $name = $args['_name'];
    unset( $args['_name'] );

    $popup = new \Mai_Popup( $args, '<p>Inner content</p>' );

    $html = $popup->get();

    // Strip the non-deterministic, filemtime-versioned asset block.
    $html = preg_replace( '#<link id="mai-popups-css".*?</script>#s', '', $html );

    // Normalize the cookie expiration timestamp. For cookie popups the legacy
    // class emits data-expire="<strtotime('+repeat')>" which is computed
    // relative to "now" and therefore changes between runs. Lock the attribute's
    // presence and position while neutralizing the volatile value.
    $html = preg_replace( '#(data-expire=")\d+(")#', '${1}EXPIRE${2}', $html );

    expect( $html )->not->toBe( '' );

    maipopups_assert_golden( $name, $html );
} )->with( 'popup_configs' );
