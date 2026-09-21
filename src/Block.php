<?php

namespace Mai\Popups;

final class Block {
    /**
     * Generates a unique popup anchor id, e.g. "#mai-popup-67abc123".
     * Used by the ACF "Link" field generator and the editor duplicate-fix script.
     */
    public static function generateAnchorId(): string {
        return \uniqid( '#mai-popup-' );
    }

    /**
     * Maps ACF block attributes + fields into Mai\Popups\Config args.
     *
     * The real production entry point for block popups: a renamed ACF field
     * key would silently break every popup, so this mapping is unit-tested.
     *
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    public static function args( array $attributes, bool $is_preview ): array {
        return [
            'class'         => $attributes['className']       ?? '',
            'position'      => $attributes['alignContent']    ?? '',
            'background'    => $attributes['backgroundColor'] ?? '',
            'color'         => $attributes['textColor']       ?? '',
            'id'            => (string) \get_field( 'id' ),
            'trigger'       => (string) \get_field( 'trigger' ),
            'animate'       => (string) \get_field( 'animate' ),
            'distance'      => (string) \get_field( 'distance' ),
            'delay'         => (string) \get_field( 'delay' ),
            'width'         => (string) \get_field( 'width' ),
            'padding'       => (string) \get_field( 'padding' ),
            'repeat'        => (string) \get_field( 'repeat' ),
            'repeat_roles'  => (array) \get_field( 'repeat_roles' ),
            'disable_close' => (bool) \get_field( 'disable_close' ),
            'preview'       => $is_preview,
        ];
    }

    /**
     * Whether to keep the assets enqueued while a block with no rendered content rendered.
     *
     * Registered from render(), so it only exists on requests that render a popup.
     *
     * Since WP 6.9, WP_Block::render() dequeues whatever a block enqueued while rendering,
     * if that block returned no content. The popup block returns nothing, because it prints
     * its markup at wp_footer, so everything its inner blocks enqueued gets thrown away. A
     * cover block inside a popup loses core's wp-block-cover styles, for example, unless
     * something else on the page happens to use the same block.
     */
    public static function keepAssets( bool $enqueue, string $blockName ): bool {
        return 'acf/mai-popup' === $blockName ? true : $enqueue;
    }

    /** @param array<string,mixed> $attributes */
    public static function render( array $attributes, string $content, bool $is_preview ): void {
        $args = self::args( $attributes, $is_preview );

        // Core decides whether to dequeue this block's assets once render() returns below.
        \add_filter( 'enqueue_empty_block_content_assets', [ self::class, 'keepAssets' ], 10, 2 );

        if ( $is_preview ) {
            $template = \wp_json_encode( [ [ 'core/paragraph', [], [] ] ] );
            $content  = sprintf( '<InnerBlocks template="%s" />', \esc_attr( $template ) );
        }

        ( new Popup( Config::fromArray( $args ), \do_shortcode( $content ) ) )->render();
    }
}
