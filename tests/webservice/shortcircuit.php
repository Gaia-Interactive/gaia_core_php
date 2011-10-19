<?php
include __DIR__ . '/../common.php';

use Gaia\ShortCircuit\Router;
Router::setAppDir(__DIR__ . '/../shortcircuit/app/');
Router::run();