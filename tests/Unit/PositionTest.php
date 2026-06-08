<?php

use Mai\Popups\Position;
use Mai\Popups\Enum\Align;

test( 'position parses "center center" as modal', function () {
    $p = Position::fromString( 'center center' );
    expect( $p->vertical )->toBe( Align::Center );
    expect( $p->horizontal )->toBe( Align::Center );
    expect( $p->isModal() )->toBeTrue();
} );

test( 'position parses "top right" as non-modal start/end', function () {
    $p = Position::fromString( 'top right' );
    expect( $p->vertical )->toBe( Align::Start );
    expect( $p->horizontal )->toBe( Align::End );
    expect( $p->isModal() )->toBeFalse();
} );
