<?php

use Mai\Popups\Block;

test( 'two generated anchor ids are unique', function () {
    expect( Block::generateAnchorId() )->not->toBe( Block::generateAnchorId() );
    expect( Block::generateAnchorId() )->toStartWith( '#mai-popup-' );
} );
