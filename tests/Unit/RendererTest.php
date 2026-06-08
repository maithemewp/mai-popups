<?php

use Mai\Popups\Config;
use Mai\Popups\Renderer;
use Mai\Popups\Cookies;

beforeEach( function () {
    maipopups_stub_wp_helpers();
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( false );
    \Brain\Monkey\Functions\when( 'current_user_can' )->justReturn( false );
} );

dataset( 'renderer_all', [
    'modal-time'      => [ 'modal-time',      [ 'trigger' => 'time', 'delay' => '3', 'position' => 'center center', 'animate' => 'fade', 'width' => '600px', 'padding' => 'md' ] ],
    'modal-scroll'    => [ 'modal-scroll',    [ 'trigger' => 'scroll', 'distance' => '50', 'position' => 'center center', 'animate' => 'up' ] ],
    'bar-bottom-load' => [ 'bar-bottom-load', [ 'trigger' => 'load', 'position' => 'end center', 'animate' => 'up', 'width' => '100%' ] ],
    'corner-manual'   => [ 'corner-manual',   [ 'trigger' => 'manual', 'position' => 'end end', 'id' => 'mai-popup-fixed', 'disable_close' => true ] ],
    'colored-padding' => [ 'colored-padding', [ 'trigger' => 'time', 'repeat' => '7 days', 'background' => 'primary', 'color' => 'white', 'padding' => 'lg', 'class' => 'my-popup' ] ],
] );

test( 'Renderer (with Cookies) reproduces ALL legacy snapshots', function ( string $name, array $args ) {
    $html = ( new Renderer( new Cookies() ) )->render( Config::fromArray( $args ), '<p>Inner content</p>' );
    $html = preg_replace( '#<link id="mai-popups-css".*?</script>#s', '', $html );
    $html = preg_replace( '#(data-expire=")\d+(")#', '$1EXPIRE$2', $html );
    maipopups_assert_golden( $name, $html );
} )->with( 'renderer_all' );

test( 'empty position emits NEITHER data-horizontal NOR data-vertical', function () {
    $html = ( new Renderer( new Cookies() ) )->render( Config::fromArray( [ 'position' => '' ] ), '<p>x</p>' );
    expect( $html )->not->toContain( 'data-horizontal' );
    expect( $html )->not->toContain( 'data-vertical' );
} );

test( 'default position (center center) emits both position attrs', function () {
    $html = ( new Renderer( new Cookies() ) )->render( Config::fromArray( [] ), '<p>x</p>' );
    expect( $html )->toContain( 'data-horizontal="center" data-vertical="center"' );
} );
