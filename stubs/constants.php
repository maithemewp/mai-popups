<?php

/**
 * Stubs for the plugin's runtime-defined constants.
 *
 * These are defined at runtime in mai-popups.php via define() inside the
 * bootstrap, so PHPStan (which performs static analysis without executing the
 * plugin) cannot see them. Declaring them here lets PHPStan resolve references
 * to them across src/. This file is never loaded at runtime.
 */

define( 'MAI_POPUPS_VERSION', '0.0.0' );
define( 'MAI_POPUPS_PLUGIN_DIR', '' );
define( 'MAI_POPUPS_PLUGIN_URL', '' );
define( 'MAI_POPUPS_PLUGIN_FILE', '' );
define( 'MAI_POPUPS_BASENAME', '' );
