<?php

use Mai\Popups\Block;
use Brain\Monkey\Functions;

beforeEach( fn () => maipopups_stub_wp_helpers() );

test( 'args() maps each ACF field into the right Config-arg slot', function () {
    // Known field map: every get_field( $key ) returns a distinct sentinel so a
    // renamed key (or a swapped slot) shows up as the wrong value in the assertion.
    $map = [
        'id'            => 'mai-popup-abc',
        'trigger'       => 'time',
        'animate'       => 'down',
        'distance'      => '75',
        'delay'         => '4',
        'width'         => '600px',
        'padding'       => 'lg',
        'repeat'        => '7 days',
        'repeat_roles'  => [ 'administrator', 'editor' ],
        'disable_close' => true,
    ];

    Functions\when( 'get_field' )->alias( fn ( $key ) => $map[ $key ] ?? '' );

    $args = Block::args( [], false );

    // ACF field -> Config arg slot.
    expect( $args['id'] )->toBe( 'mai-popup-abc' );
    expect( $args['trigger'] )->toBe( 'time' );
    expect( $args['animate'] )->toBe( 'down' );
    expect( $args['distance'] )->toBe( '75' );
    expect( $args['delay'] )->toBe( '4' );
    expect( $args['width'] )->toBe( '600px' );
    expect( $args['padding'] )->toBe( 'lg' );
    expect( $args['repeat'] )->toBe( '7 days' );
    expect( $args['repeat_roles'] )->toBe( [ 'administrator', 'editor' ] );
    expect( $args['disable_close'] )->toBeTrue();
} );

test( 'args() maps block attributes into the right Config-arg slot', function () {
    Functions\when( 'get_field' )->justReturn( '' );

    $attributes = [
        'className'       => 'my-custom-class',
        'alignContent'    => 'end center',
        'backgroundColor' => 'primary',
        'textColor'       => 'white',
    ];

    $args = Block::args( $attributes, false );

    // Block attribute -> Config arg slot (the WP-core renames live here).
    expect( $args['class'] )->toBe( 'my-custom-class' );      // className   -> class
    expect( $args['position'] )->toBe( 'end center' );        // alignContent -> position
    expect( $args['background'] )->toBe( 'primary' );         // backgroundColor -> background
    expect( $args['color'] )->toBe( 'white' );                // textColor   -> color
} );

test( 'args() defaults missing block attributes to empty strings', function () {
    Functions\when( 'get_field' )->justReturn( '' );

    $args = Block::args( [], false );

    expect( $args['class'] )->toBe( '' );
    expect( $args['position'] )->toBe( '' );
    expect( $args['background'] )->toBe( '' );
    expect( $args['color'] )->toBe( '' );
} );

test( 'args() casts get_field types: string for scalars, array for roles, bool for disable_close', function () {
    // get_field can return non-strings; the mapping must cast deterministically.
    $map = [
        'id'            => 12345,        // int -> "12345"
        'repeat_roles'  => 'admin',      // scalar -> [ 'admin' ]
        'disable_close' => 1,            // truthy int -> true
    ];

    Functions\when( 'get_field' )->alias( fn ( $key ) => $map[ $key ] ?? '' );

    $args = Block::args( [], false );

    expect( $args['id'] )->toBe( '12345' );
    expect( $args['repeat_roles'] )->toBe( [ 'admin' ] );
    expect( $args['disable_close'] )->toBeTrue();
} );

test( 'args() reflects the is_preview argument in the preview slot', function () {
    Functions\when( 'get_field' )->justReturn( '' );

    expect( Block::args( [], true )['preview'] )->toBeTrue();
    expect( Block::args( [], false )['preview'] )->toBeFalse();
} );
