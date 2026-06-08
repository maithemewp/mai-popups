<?php

namespace Mai\Popups\Enum;

enum Trigger: string {
    case Manual = 'manual';
    case Load   = 'load';
    case Scroll = 'scroll';
    case Time   = 'time';

    public static function tryFromString( string $value ): self {
        return self::tryFrom( $value ) ?? self::Manual;
    }
}
