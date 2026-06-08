<?php

namespace Mai\Popups;

final class Conditions {
    public function passes( Config $config ): bool {
        return $config->condition;
    }
}
