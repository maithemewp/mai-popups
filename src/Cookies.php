<?php

namespace Mai\Popups;

use Mai\Popups\Defaults;
use Mai\Popups\Enum\Trigger;

final class Cookies {
    public function shouldUse( Config $config ): bool {
        $use = ! $config->preview
            && in_array( $config->trigger, [ Trigger::Time, Trigger::Scroll ], true )
            && '' !== $config->repeat;

        if ( $use && $config->repeatRoles && \is_user_logged_in() ) {
            foreach ( $config->repeatRoles as $role ) {
                if ( \current_user_can( $role ) ) { return false; }
            }
        }

        return $use;
    }

    public function expires( Config $config ): int {
        $repeat = trim( $config->repeat );

        // Repeat accepts anything strtotime() understands (e.g. "7 days", "2 weeks").
        // Safety net: a bare number has no unit, so treat it as days — strtotime("+7")
        // on its own is invalid.
        $arg   = is_numeric( $repeat ) ? "+{$repeat} days" : '+' . $repeat;
        $stamp = \strtotime( $arg );

        // An unparseable repeat returns false (which would cast to 0 / epoch
        // 1970 and re-show the popup every load). Fall back to the default.
        if ( false === $stamp ) {
            $stamp = \strtotime( '+' . Defaults::get()['repeat'] );
        }

        return (int) $stamp;
    }
}
