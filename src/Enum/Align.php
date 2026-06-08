<?php

namespace Mai\Popups\Enum;

enum Align: string {
    case Start  = 'start';
    case Center = 'center';
    case End    = 'end';

    public static function fromToken( string $token ): self {
        return match ( trim( $token ) ) {
            'top', 'left', 'start'   => self::Start,
            'bottom', 'right', 'end' => self::End,
            default                  => self::Center,
        };
    }
}
