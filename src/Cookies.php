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
        return (int) \strtotime( '+' . $config->repeat );
    }
}
