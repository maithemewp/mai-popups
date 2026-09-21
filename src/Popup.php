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
            // Enqueue at wp_footer, unless we're already there. Enqueuing during the block
            // render instead would not always survive: WP_Block::render() dequeues whatever
            // a block enqueued if that block rendered empty, and while the popup block itself
            // opts out via Block::keepAssets(), an ancestor block that also renders empty
            // (a synced pattern holding only a popup, say) does not. By wp_footer the block
            // render stack has unwound, so there is nothing left to dequeue us.
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
