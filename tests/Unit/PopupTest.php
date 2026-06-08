<?php

use Mai\Popups\Config;
use Mai\Popups\Popup;
use Brain\Monkey\Functions;

beforeEach( function () {
    maipopups_stub_wp_helpers();

    // Conditions/Cookies path needs these; keep them harmless.
    Functions\when( 'is_user_logged_in' )->justReturn( false );
    Functions\when( 'current_user_can' )->justReturn( false );

    // Assets::enqueue() (non-preview render) calls these; stub so they don't error.
    Functions\when( 'wp_script_is' )->justReturn( true );
    Functions\when( 'wp_enqueue_script' )->justReturn( null );
    Functions\when( 'wp_enqueue_style' )->justReturn( null );

    // Reset the static $loaded once-guard so tests don't leak across the run.
    $prop = new ReflectionProperty( Popup::class, 'loaded' );
    $prop->setAccessible( true );
    $prop->setValue( null, [] );
} );

/** Runs $fn with output buffering and returns whatever it printed. */
function maipopups_capture( callable $fn ): string {
    ob_start();
    $fn();
    return (string) ob_get_clean();
}

test( 'once-guard: same id renders the dialog exactly once', function () {
    // Both popups share an id; immediate-render path via doing_action(wp_footer)=true.
    Functions\when( 'doing_action' )->justReturn( true );
    Functions\when( 'did_action' )->justReturn( false );

    $html = maipopups_capture( function () {
        ( new Popup( Config::fromArray( [ 'id' => 'once-guard-x', 'trigger' => 'load' ] ), '<p>a</p>' ) )->render();
        ( new Popup( Config::fromArray( [ 'id' => 'once-guard-x', 'trigger' => 'load' ] ), '<p>b</p>' ) )->render();
    } );

    expect( substr_count( $html, 'id="once-guard-x"' ) )->toBe( 1 );
    expect( substr_count( $html, '<dialog' ) )->toBe( 1 );
} );

test( 'condition gate: condition=false produces no output and never defers to wp_footer', function () {
    Functions\when( 'doing_action' )->justReturn( false );
    Functions\when( 'did_action' )->justReturn( false );

    // If render() short-circuits on the failed condition, add_action must never fire.
    Functions\expect( 'add_action' )->never();

    $html = maipopups_capture( function () {
        ( new Popup( Config::fromArray( [ 'id' => 'cond-x', 'condition' => false, 'trigger' => 'load' ] ) ) )->render();
    } );

    expect( $html )->toBe( '' );
} );

test( 'footer timing: in the footer it prints immediately', function () {
    Functions\when( 'doing_action' )->justReturn( true );  // isFooter() === true
    Functions\when( 'did_action' )->justReturn( false );
    Functions\expect( 'add_action' )->never();             // immediate, not deferred

    $html = maipopups_capture( function () {
        ( new Popup( Config::fromArray( [ 'id' => 'footer-now', 'trigger' => 'load' ] ), '<p>x</p>' ) )->render();
    } );

    expect( $html )->toContain( 'id="footer-now"' );
    expect( $html )->toContain( '<dialog' );
} );

test( 'footer timing: outside the footer it defers via add_action(wp_footer)', function () {
    Functions\when( 'doing_action' )->justReturn( false ); // isFooter() === false
    Functions\when( 'did_action' )->justReturn( false );

    $deferred = null;
    Functions\expect( 'add_action' )
        ->once()
        ->andReturnUsing( function ( $hook, $cb ) use ( &$deferred ) {
            $deferred = $hook;
            return true;
        } );

    // Nothing should print synchronously when deferred.
    $html = maipopups_capture( function () {
        ( new Popup( Config::fromArray( [ 'id' => 'footer-later', 'trigger' => 'load' ] ), '<p>x</p>' ) )->render();
    } );

    expect( $deferred )->toBe( 'wp_footer' );
    expect( $html )->toBe( '' );
} );

test( 'preview popups print immediately as a div without touching Assets or wp_footer', function () {
    Functions\when( 'doing_action' )->justReturn( false );
    Functions\when( 'did_action' )->justReturn( false );
    Functions\expect( 'add_action' )->never();

    $html = maipopups_capture( function () {
        ( new Popup( Config::fromArray( [ 'id' => 'prev-x', 'preview' => true ] ), '<p>x</p>' ) )->render();
    } );

    expect( $html )->toContain( 'id="prev-x"' );
    expect( $html )->toContain( '<div' );
    expect( $html )->not->toContain( '<dialog' );
} );
