<?php

namespace Mai\Popups;

final class Assets {
    // Once-per-request guard (legacy used `static $first` in get_scripts_styles()).
    // Static — not per-instance — so the <link>/<script> block emits exactly once
    // per page across every Popup/Assets instance.
    // NOTE: Plan 2 replaces this with wp_enqueue_* (which dedupes by handle),
    // making this static moot — but it's a live bug until then.
    private static bool $first = true;

    public function inlineHead( Config $config ): string {
        if ( ! self::$first || $config->preview ) { return ''; }
        self::$first = false;

        $suffix  = defined( 'SCRIPT_DEBUG' ) && \SCRIPT_DEBUG ? '' : '.min';
        $version = MAI_POPUPS_VERSION;
        $cssPath = MAI_POPUPS_PLUGIN_DIR . "assets/css/mai-popups{$suffix}.css";
        $cssUrl  = MAI_POPUPS_PLUGIN_URL . "assets/css/mai-popups{$suffix}.css";
        $jsPath  = MAI_POPUPS_PLUGIN_DIR . "assets/js/mai-popups{$suffix}.js";
        $jsUrl   = MAI_POPUPS_PLUGIN_URL . "assets/js/mai-popups{$suffix}.js";

        return sprintf( '<link id="mai-popups-css" rel="stylesheet" href="%s?ver=%s">', $cssUrl, $version . '.' . \date( 'njYHi', \filemtime( $cssPath ) ) )
             . sprintf( '<script id="mai-popups-js" src="%s?ver=%s" defer></script>', $jsUrl, $version . '.' . \date( 'njYHi', \filemtime( $jsPath ) ) );
    }
}
