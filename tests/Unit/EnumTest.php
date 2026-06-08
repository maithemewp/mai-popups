<?php

use Mai\Popups\Enum\Trigger;
use Mai\Popups\Enum\Animation;
use Mai\Popups\Enum\Align;

test( 'trigger parses known values and falls back to manual', function () {
    expect( Trigger::from( 'time' ) )->toBe( Trigger::Time );
    expect( Trigger::tryFromString( 'nope' ) )->toBe( Trigger::Manual );
} );

test( 'animation falls back to fade', function () {
    expect( Animation::tryFromString( 'up' ) )->toBe( Animation::Up );
    expect( Animation::tryFromString( 'xxx' ) )->toBe( Animation::Fade );
} );

test( 'align maps legacy tokens to start/center/end', function () {
    expect( Align::fromToken( 'top' ) )->toBe( Align::Start );
    expect( Align::fromToken( 'bottom' ) )->toBe( Align::End );
    expect( Align::fromToken( 'left' ) )->toBe( Align::Start );
    expect( Align::fromToken( 'right' ) )->toBe( Align::End );
    expect( Align::fromToken( 'center' ) )->toBe( Align::Center );
    expect( Align::fromToken( 'garbage' ) )->toBe( Align::Center );
} );
