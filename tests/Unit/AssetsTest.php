<?php

use Mai\Popups\Assets;
use Brain\Monkey\Functions;

beforeEach( function () {
    maipopups_stub_wp_helpers();

    // Real plugin dir so the require of build/mai-popups.asset.php resolves.
    if ( ! defined( 'MAI_POPUPS_PLUGIN_DIR' ) ) {
        define( 'MAI_POPUPS_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
    }
    if ( ! defined( 'MAI_POPUPS_PLUGIN_URL' ) ) {
        define( 'MAI_POPUPS_PLUGIN_URL', 'https://example.test/wp-content/plugins/mai-popups/' );
    }
} );

test( 'register() registers the mai-popups script and style from the built bundle', function () {
    $script = null;
    $style  = null;

    Functions\expect( 'wp_register_script' )
        ->once()
        ->andReturnUsing( function ( $handle, $src, $deps, $ver, $args ) use ( &$script ) {
            $script = compact( 'handle', 'src', 'deps', 'ver', 'args' );
            return true;
        } );

    Functions\expect( 'wp_register_style' )
        ->once()
        ->andReturnUsing( function ( $handle, $src, $deps, $ver ) use ( &$style ) {
            $style = compact( 'handle', 'src', 'deps', 'ver' );
            return true;
        } );

    Assets::register();

    expect( $script['handle'] )->toBe( 'mai-popups' );
    expect( $script['src'] )->toEndWith( 'build/mai-popups.js' );
    expect( $script['deps'] )->toBeArray();
    expect( $script['args'] )->toMatchArray( [ 'strategy' => 'defer', 'in_footer' => true ] );

    expect( $style['handle'] )->toBe( 'mai-popups' );
    expect( $style['src'] )->toEndWith( 'build/mai-popups.css' );
    expect( $style['deps'] )->toBe( [] );
} );

test( 'enqueue() enqueues the registered script and style by handle', function () {
    $script = null;
    $style  = null;

    // Pretend the handle is already registered (normal flow: register() ran on wp_enqueue_scripts).
    Functions\when( 'wp_script_is' )->justReturn( true );

    Functions\expect( 'wp_enqueue_script' )
        ->once()
        ->andReturnUsing( function ( $handle ) use ( &$script ) { $script = $handle; } );
    Functions\expect( 'wp_enqueue_style' )
        ->once()
        ->andReturnUsing( function ( $handle ) use ( &$style ) { $style = $handle; } );

    Assets::enqueue();

    expect( $script )->toBe( 'mai-popups' );
    expect( $style )->toBe( 'mai-popups' );
} );
