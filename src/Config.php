<?php

namespace Mai\Popups;

use Mai\Popups\Enum\Trigger;
use Mai\Popups\Enum\Animation;

final readonly class Config {
    /** @param string[] $repeatRoles */
    public function __construct(
        public string $id,
        public string $class,
        public Trigger $trigger,
        public Animation $animate,
        public string $distance,
        public string $delay,
        public Position $position,
        public string $width,
        public string $padding,
        public string $repeat,
        public array $repeatRoles,
        public bool $disableClose,
        public string $background,
        public string $color,
        public bool $condition,
        public bool $preview,
    ) {}

    /** @param array<string,mixed> $args */
    public static function fromArray( array $args ): self {
        $args = shortcode_atts( Defaults::get(), $args, 'mai_popup' );

        return new self(
            id:           sanitize_key( $args['id'] ),
            class:        esc_attr( $args['class'] ),
            trigger:      Trigger::tryFromString( sanitize_key( $args['trigger'] ) ),
            animate:      Animation::tryFromString( sanitize_key( $args['animate'] ) ),
            distance:     self::float( $args['distance'] ),
            delay:        self::float( $args['delay'] ),
            position:     Position::fromString( esc_html( $args['position'] ) ),
            width:        trim( esc_html( $args['width'] ) ),
            padding:      sanitize_key( $args['padding'] ),
            repeat:       trim( esc_html( $args['repeat'] ) ),
            repeatRoles:  array_map( 'sanitize_key', (array) $args['repeat_roles'] ),
            disableClose: rest_sanitize_boolean( $args['disable_close'] ),
            background:   sanitize_key( $args['background'] ),
            color:        sanitize_key( $args['color'] ),
            condition:    rest_sanitize_boolean( is_callable( $args['condition'] ) ? $args['condition']() : $args['condition'] ),
            preview:      rest_sanitize_boolean( $args['preview'] ),
        );
    }

    private static function float( mixed $value ): string {
        $value = sanitize_text_field( (string) $value );
        return str_ends_with( $value, '.0' ) ? substr( $value, 0, -2 ) : $value;
    }
}
