<?php
include __DIR__ . '/../../common.php';

use Gaia\ShortCircuit;
use Gaia\ShortCircuit\Resolver;
use Gaia\ShortCircuit\PatternResolver;

$patterns = array(
'/go/(id)/'                 => 'nested/test',
'/foo/bar/(a)/test/(b)'     => 'nested/deep/test',
'/idtest/(id)/'             => 'id',
'/lt/(a)/(b)/(c)'           => 'linktest',                 
);

ShortCircuit::resolver( new Resolver(__DIR__ . '/../../shortcircuit/app/', $patterns) );
ShortCircuit::run();