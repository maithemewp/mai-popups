<?php

use Mai\Popups\Config;
use Mai\Popups\Renderer;
use Mai\Popups\Cookies;

beforeEach( function () {
    maipopups_stub_wp_helpers();
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( false );
    \Brain\Monkey\Functions\when( 'current_user_can' )->justReturn( false );
    \Brain\Monkey\Functions\when( 'strtotime' )->justReturn( 1893456000 );
} );

test( 'cookie popup adds data-cookie and data-expire', function () {
    $config = Config::fromArray( [ 'trigger' => 'time', 'repeat' => '7 days', 'delay' => '3' ] );
    $html   = ( new Renderer( new Cookies() ) )->render( $config, '<p>x</p>' );
    expect( $html )->toContain( 'data-cookie="true"' );
    expect( $html )->toContain( 'data-expire="1893456000"' );
} );
