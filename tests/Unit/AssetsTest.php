<?php

use Mai\Popups\Assets;
use Mai\Popups\Config;

beforeEach( function () {
    maipopups_stub_wp_helpers();

    // Real plugin dir so filemtime() resolves against the shipped assets.
    if ( ! defined( 'MAI_POPUPS_VERSION' ) ) {
        define( 'MAI_POPUPS_VERSION', 'test' );
    }
    if ( ! defined( 'MAI_POPUPS_PLUGIN_DIR' ) ) {
        define( 'MAI_POPUPS_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
    }
    if ( ! defined( 'MAI_POPUPS_PLUGIN_URL' ) ) {
        define( 'MAI_POPUPS_PLUGIN_URL', 'https://example.test/wp-content/plugins/mai-popups/' );
    }
} );

// Legacy emitted the <link>/<script> block exactly once per request via a
// `static $first` in get_scripts_styles(). Assets::$first is now static too,
// so the asset markup is shared across every Assets instance per page.
test( 'asset block emits once per page across multiple Assets instances', function () {
    $config = Config::fromArray( [ 'preview' => false ] );

    $first  = ( new Assets() )->inlineHead( $config );
    $second = ( new Assets() )->inlineHead( $config );

    expect( $first )->toContain( '<link id="mai-popups-css"' );
    expect( $first )->toContain( '<script id="mai-popups-js"' );
    expect( $second )->toBe( '' );
} );
