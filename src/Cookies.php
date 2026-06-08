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
        $stamp = \strtotime( '+' . $config->repeat );

        // An unparseable repeat returns false (which would cast to 0 / epoch
        // 1970 and re-show the popup every load). Fall back to the default.
        if ( false === $stamp ) {
            $stamp = \strtotime( '+' . Defaults::get()['repeat'] );
        }

        return (int) $stamp;
    }
}
