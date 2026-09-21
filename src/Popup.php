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
            Assets::enqueue();
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
