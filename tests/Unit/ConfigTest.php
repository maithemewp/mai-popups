<?php

use Mai\Popups\Config;
use Mai\Popups\Enum\Trigger;

beforeEach( fn () => maipopups_stub_wp_helpers() );

test( 'config applies defaults and parses types', function () {
    $config = Config::fromArray( [ 'trigger' => 'time', 'delay' => '3.0', 'distance' => '50.0' ] );
    expect( $config->trigger )->toBe( Trigger::Time );
    expect( $config->delay )->toBe( '3' );
    expect( $config->distance )->toBe( '50' );
    expect( $config->disableClose )->toBeFalse();
} );

test( 'config sanitizes roles to array', function () {
    $config = Config::fromArray( [ 'repeat_roles' => [ 'administrator', 'editor' ] ] );
    expect( $config->repeatRoles )->toBe( [ 'administrator', 'editor' ] );
    expect( $config->position->isModal() )->toBeTrue();
} );

test( 'config resolves a callable condition to a bool', function () {
    $config = Config::fromArray( [ 'condition' => fn () => false ] );
    expect( $config->condition )->toBeFalse();
} );
