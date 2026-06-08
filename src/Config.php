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
        public ?Position $position,
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
        $args = \shortcode_atts( Defaults::get(), $args, 'mai_popup' );

        // Mirror legacy truthiness: an empty position string yields no Position
        // (legacy emitted position attrs only inside `if ( $this->args['position'] )`).
        $pos      = \sanitize_text_field( $args['position'] );
        $position = $pos ? Position::fromString( $pos ) : null;

        return new self(
            id:           \sanitize_key( $args['id'] ),
            class:        \sanitize_text_field( $args['class'] ),
            trigger:      Trigger::tryFromString( \sanitize_key( $args['trigger'] ) ),
            animate:      Animation::tryFromString( \sanitize_key( $args['animate'] ) ),
            distance:     self::float( $args['distance'] ),
            delay:        self::float( $args['delay'] ),
            position:     $position,
            width:        trim( \sanitize_text_field( $args['width'] ) ),
            padding:      \sanitize_key( $args['padding'] ),
            repeat:       trim( \sanitize_text_field( $args['repeat'] ) ),
            repeatRoles:  array_map( '\sanitize_key', (array) $args['repeat_roles'] ),
            disableClose: \rest_sanitize_boolean( self::boolish( $args['disable_close'] ) ),
            background:   \sanitize_key( $args['background'] ),
            color:        \sanitize_key( $args['color'] ),
            condition:    \rest_sanitize_boolean( self::boolish( is_callable( $args['condition'] ) ? $args['condition']() : $args['condition'] ) ),
            preview:      \rest_sanitize_boolean( self::boolish( $args['preview'] ) ),
        );
    }

    private static function float( mixed $value ): string {
        $value = \sanitize_text_field( (string) $value );
        return str_ends_with( $value, '.0' ) ? substr( $value, 0, -2 ) : $value;
    }

    /**
     * Narrows a mixed value to the bool|string|int union that
     * rest_sanitize_boolean() accepts, preserving its behavior: scalar
     * bool/string/int pass through untouched, while any other type
     * (null, array, object) reduces to its boolean cast exactly as
     * rest_sanitize_boolean()'s own (bool) fallback would.
     */
    private static function boolish( mixed $value ): bool|string|int {
        return match ( true ) {
            is_bool( $value ), is_string( $value ), is_int( $value ) => $value,
            default => (bool) $value,
        };
    }
}
