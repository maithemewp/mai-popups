<?php

namespace Mai\Popups;

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

        // Repeat is now an integer number of days. Pre-0.6.0 popups (and the
        // template-tag API) may still pass a strtotime duration string like
        // "7 days" or "2 weeks", so handle both: a bare integer means days,
        // anything else is treated as a literal strtotime modifier.
        $arg = ctype_digit( $repeat ) ? "+{$repeat} days" : '+' . $repeat;

        return (int) \strtotime( $arg );
    }
}
