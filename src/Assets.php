<?php

namespace Mai\Popups;

final class Assets {
    public static function register(): void {
        $path = MAI_POPUPS_PLUGIN_DIR . 'build/mai-popups.asset.php';

        if ( ! file_exists( $path ) ) {
            return;
        }

        /** @var array{dependencies: string[], version: string} $asset */
        $asset = require $path;

        \wp_register_script(
            'mai-popups',
            MAI_POPUPS_PLUGIN_URL . 'build/mai-popups.js',
            $asset['dependencies'],
            $asset['version'],
            [ 'strategy' => 'defer', 'in_footer' => true ]
        );

        \wp_register_style(
            'mai-popups',
            MAI_POPUPS_PLUGIN_URL . 'build/mai-popups.css',
            [],
            $asset['version']
        );
    }

    public static function enqueue(): void {
        if ( ! \wp_script_is( 'mai-popups', 'registered' ) ) {
            self::register();
        }

        \wp_enqueue_script( 'mai-popups' );
        \wp_enqueue_style( 'mai-popups' );
    }
}
