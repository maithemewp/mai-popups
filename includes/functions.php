<?php

defined( 'ABSPATH' ) || die;

use Mai\Popups\Config;
use Mai\Popups\Defaults;
use Mai\Popups\Popup;

/**
 * Render a popup. Public procedural API (template tag) wrapping Mai\Popups\Popup.
 *
 * @param array<string,mixed> $args    Popup args. See Mai\Popups\Defaults::get().
 * @param string              $content Popup inner content.
 */
function mai_do_popup( array $args = [], string $content = '' ): void {
    ( new Popup( Config::fromArray( $args ), $content ) )->render();
}

/**
 * Get the filterable popup default args. Public procedural API for Mai\Popups\Defaults.
 *
 * @return array<string,mixed>
 */
function maipopups_get_defaults(): array {
    return Defaults::get();
}
