<?php

namespace Mai\Popups;

final class Popup {
    /** @var array<string,bool> */
    private static array $loaded = [];

    public function __construct(
        private Config $config,
        private string $content = '',
        private ?Renderer $renderer = null,
        private ?Conditions $conditions = null,
    ) {
        $this->renderer   ??= new Renderer( new Cookies() );
        $this->conditions ??= new Conditions();
    }

    public function render(): void {
        $id = ltrim( $this->config->id, '#' );
        if ( isset( self::$loaded[ $id ] ) ) { return; }
        if ( ! $this->conditions->passes( $this->config ) ) { return; }

        if ( ! $this->config->preview ) {
            // Defer the enqueue to wp_footer (as the pre-rewrite version did), unless we're
            // already in the footer. A synchronous enqueue here, during block render, does
            // not survive to when footer scripts print: the script queue is restored to its
            // pre-block state as do_blocks() moves on, so the assets never load. Enqueuing at
            // wp_footer (priority 10, before wp_print_footer_scripts at 20) is the safe point.
            if ( $this->isFooter() ) {
                Assets::enqueue();
            } else {
                \add_action( 'wp_footer', [ Assets::class, 'enqueue' ] );
            }
        }

        $output = fn () => print $this->renderer->render( $this->config, $this->content );

        if ( $this->config->preview || $this->isFooter() ) {
            $output();
        } else {
            \add_action( 'wp_footer', $output );
        }

        self::$loaded[ $id ] = true;
    }

    private function isFooter(): bool {
        return (bool) ( \doing_action( 'wp_footer' ) || \did_action( 'wp_footer' ) );
    }
}
