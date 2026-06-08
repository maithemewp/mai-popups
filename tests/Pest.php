<?php

use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

uses()
    ->beforeEach(function () { setUp(); require_once __DIR__ . '/Helpers.php'; })
    ->afterEach(function () { tearDown(); })
    ->in('Unit');
