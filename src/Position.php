<?php

namespace Mai\Popups;

use Mai\Popups\Enum\Align;

final readonly class Position {
    public function __construct(
        public Align $vertical,
        public Align $horizontal,
    ) {}

    public static function fromString( string $position ): self {
        $parts      = array_values( array_filter( array_map( 'trim', explode( ' ', $position ) ) ) );
        $vertical   = Align::fromToken( $parts[0] ?? 'center' );
        $horizontal = Align::fromToken( $parts[1] ?? ( $parts[0] ?? 'center' ) );

        return new self( $vertical, $horizontal );
    }

    public function isModal(): bool {
        return Align::Center === $this->vertical && Align::Center === $this->horizontal;
    }
}
