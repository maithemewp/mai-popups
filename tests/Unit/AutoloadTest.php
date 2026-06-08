<?php

test( 'Mai\\Popups namespace autoloads', function () {
    expect( class_exists( \Mai\Popups\Plugin::class ) )->toBeTrue();
} );
