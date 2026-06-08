<?php

namespace Mai\Popups;

final class Assets {
    public static function register(): void {
        /** @var array{dependencies: string[], version: string} $asset */
        $asset = require MAI_POPUPS_PLUGIN_DIR . 'build/mai-popups.asset.php';

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
        \wp_enqueue_script( 'mai-popups' );
        \wp_enqueue_style( 'mai-popups' );
    }
}
