<?php

namespace Mai\Popups\Enum;

enum Animation: string {
    case Fade = 'fade';
    case Up   = 'up';
    case Down = 'down';

    public static function tryFromString( string $value ): self {
        return self::tryFrom( $value ) ?? self::Fade;
    }
}
