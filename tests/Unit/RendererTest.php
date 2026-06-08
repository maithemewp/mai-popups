<?php

use Mai\Popups\Config;
use Mai\Popups\Renderer;

beforeEach( function () {
    maipopups_stub_wp_helpers();
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( false );
    \Brain\Monkey\Functions\when( 'current_user_can' )->justReturn( false );
} );

// Only the non-cookie cases reach full byte-parity in this task; cookie cases are completed when Cookies is wired in (later task).
dataset( 'renderer_noncookie', [
    'corner-manual'   => [ 'corner-manual',   [ 'trigger' => 'manual', 'position' => 'end end', 'id' => 'mai-popup-fixed', 'disable_close' => true ] ],
    'bar-bottom-load' => [ 'bar-bottom-load', [ 'trigger' => 'load', 'position' => 'end center', 'animate' => 'up', 'width' => '100%' ] ],
] );

test( 'Renderer reproduces the legacy markup for non-cookie cases', function ( string $name, array $args ) {
    $html = ( new Renderer() )->render( Config::fromArray( $args ), '<p>Inner content</p>' );
    $html = preg_replace( '#<link id="mai-popups-css".*?</script>#s', '', $html );
    $html = preg_replace( '#(data-expire=")\d+(")#', '$1EXPIRE$2', $html );
    maipopups_assert_golden( $name, $html ); // compares against the SAME golden files Task 3 wrote
} )->with( 'renderer_noncookie' );
