<?php

use Mai\Popups\Config;
use Mai\Popups\Cookies;

beforeEach( function () {
    maipopups_stub_wp_helpers();
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( false );
} );

test( 'cookie used for timed popup with repeat', function () {
    $config = Config::fromArray( [ 'trigger' => 'time', 'repeat' => '7 days' ] );
    expect( ( new Cookies() )->shouldUse( $config ) )->toBeTrue();
} );

test( 'no cookie for manual trigger', function () {
    $config = Config::fromArray( [ 'trigger' => 'manual', 'repeat' => '7 days' ] );
    expect( ( new Cookies() )->shouldUse( $config ) )->toBeFalse();
} );

test( 'role exemption disables cookie for matching logged-in user', function () {
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( true );
    \Brain\Monkey\Functions\when( 'current_user_can' )->justReturn( true );
    $config = Config::fromArray( [ 'trigger' => 'time', 'repeat' => '7 days', 'repeat_roles' => [ 'administrator' ] ] );
    expect( ( new Cookies() )->shouldUse( $config ) )->toBeFalse();
} );

test( 'expires(): a bare integer repeat is treated as days (safety net)', function () {
    $expires = ( new Cookies() )->expires( Config::fromArray( [ 'repeat' => '7' ] ) );
    // ~7 days out — NOT epoch 1970, which an invalid strtotime("+7") would yield.
    expect( $expires )->toBeGreaterThan( time() + ( 6 * 86400 ) );
    expect( $expires )->toBeLessThan( time() + ( 8 * 86400 ) );
} );

test( 'expires(): a strtotime string repeat is honored', function () {
    $expires = ( new Cookies() )->expires( Config::fromArray( [ 'repeat' => '2 weeks' ] ) );
    expect( $expires )->toBeGreaterThan( time() + ( 13 * 86400 ) );
} );
