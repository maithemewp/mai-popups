<?php

namespace Mai\Popups;

use Mai\Popups\Enum\Trigger;

final class Renderer {
    public function __construct(
        private ?Cookies $cookies = null,
    ) {}

    /**
     * Builds the popup markup for a given config and content.
     *
     * Ported from the legacy Mai_Popup::get().
     */
    public function render( Config $config, string $content ): string {
        $id    = ltrim( $config->id, '#' );
        $html  = '';
        $width = sprintf( '%s%s', $config->width, is_numeric( $config->width ) ? 'px' : '' );
        $atts  = '';
        $args  = [
            'id'           => $id,
            'class'        => 'mai-popup',
            'style'        => '',
            'data-type'    => $config->trigger->value,
            'data-animate' => $config->animate->value,
            'data-close'   => $config->disableClose ? 'false' : 'true',
        ];

        // Adds custom classes.
        if ( $config->class ) {
            $args['class'] .= ' ' . $config->class;
        }

        // Add padding class.
        if ( $config->padding ) {
            $args['class'] .= sprintf( ' has-%s-padding', $config->padding );
        }

        // Add background color.
        if ( $config->background ) {
            $name = 'link' === $config->background ? 'links' : $config->background;
            $args['class'] .= sprintf( ' has-%s-background-color', $name );
            $args['style'] .= sprintf( '--mai-popup-close-background:var(--color-%s);', $config->background );
        }

        // Add text color.
        if ( $config->color ) {
            $name = 'link' === $config->color ? 'links' : $config->color;
            $args['class'] .= sprintf( ' has-%s-color', $name );
            $args['style'] .= sprintf( '--mai-popup-close-color:var(--color-%s);', $config->color );
        }

        // Adds width attributes.
        if ( $width ) {
            $args['style'] .= sprintf( '--mai-popup-max-width:%s;', $width );

            if ( in_array( $width, [ '100%', '100vw' ] ) ) {
                $args['data-width'] = 'full';
            }
        }

        // Adds position custom properties.
        // Legacy emitted these only inside `if ( $this->args['position'] )`, so
        // an empty position (null Config::$position) emits no attrs at all.
        if ( null !== $config->position ) {
            $args['data-horizontal'] = $config->position->horizontal->value;
            $args['data-vertical']   = $config->position->vertical->value;
        }

        // Centered modals get a flag so the JS uses showModal() vs show().
        if ( null !== $config->position && $config->position->isModal() ) {
            $args['data-modal'] = 'true';
        }

        // Sets trigger attributes.
        match ( $config->trigger ) {
            Trigger::Time   => $args['data-delay']    = (float) $config->delay * 1000,
            Trigger::Scroll => $args['data-distance'] = (int) $config->distance,
            default         => null,
        };

        // If a cookie popup, add cookie attributes.
        if ( $this->cookies?->shouldUse( $config ) ) {
            $args['data-cookie'] = 'true';
            $args['data-expire'] = (string) $this->cookies->expires( $config );
        }

        // Build args.
        foreach ( $args as $att => $value ) {
            if ( ! $value ) {
                continue;
            }

            $atts .= sprintf( ' %s="%s"', $att, trim( (string) $value ) );
        }

        // Set tag.
        $tag = $config->preview ? 'div' : 'dialog';

        // Build HTML.
        $html .= sprintf( '<%s%s>', $tag, $atts );
            $html .= $this->closeButton( $config );
            $html .= $content;
        $html .= sprintf( '</%s>', $tag );

        return $html;
    }

    /**
     * Gets close button markup. Omitted when close is disabled.
     */
    private function closeButton( Config $config ): string {
        if ( $config->disableClose ) {
            return '';
        }

        $text  = \__( 'Close', 'mai-popups' );
        $class = 'mai-popup__close';

        return sprintf( '<button type="button" class="%s" aria-label="%s"></button>', $class, $text );
    }
}
