<?php

use Mai\Popups\Config;
use Mai\Popups\Cookies;

beforeEach( function () {
    maipopups_stub_wp_helpers();
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( false );
} );

test( 'cookie used for timed popup with repeat', function () {
    $config = Config::fromArray( [ 'trigger' => 'time', 'repeat' => '7' ] );
    expect( ( new Cookies() )->shouldUse( $config ) )->toBeTrue();
} );

test( 'no cookie for manual trigger', function () {
    $config = Config::fromArray( [ 'trigger' => 'manual', 'repeat' => '7' ] );
    expect( ( new Cookies() )->shouldUse( $config ) )->toBeFalse();
} );

test( 'role exemption disables cookie for matching logged-in user', function () {
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( true );
    \Brain\Monkey\Functions\when( 'current_user_can' )->justReturn( true );
    $config = Config::fromArray( [ 'trigger' => 'time', 'repeat' => '7', 'repeat_roles' => [ 'administrator' ] ] );
    expect( ( new Cookies() )->shouldUse( $config ) )->toBeFalse();
} );
