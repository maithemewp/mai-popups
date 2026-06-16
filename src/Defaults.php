<?php

namespace Mai\Popups;

final class Defaults {
    /** @return array<string,mixed> */
    public static function get(): array {
        return (array) \apply_filters( 'mai_popup_default_args', [
            'id' => '', 'class' => '', 'trigger' => 'manual', 'animate' => 'fade',
            'distance' => '50', 'delay' => '3', 'position' => 'center center',
            'width' => '', 'padding' => 'xl', 'repeat' => '7 days', 'repeat_roles' => [],
            'disable_close' => false, 'background' => '', 'color' => '', 'condition' => true,
            'preview' => false,
        ] );
    }
}
