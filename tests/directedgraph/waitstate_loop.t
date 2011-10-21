#!/usr/bin/env php
<?php

include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DirectedGraph\Node;
use Gaia\DirectedGraph\Execution;

Tap::plan(5);

// one --> two --> three
//  |       |
//  +---<---+

$one = new Node();
$two = new Node();
$three = new Node();
$one->add("continue",$two);
$two->add("continue",$three);
$two->add("back", $one);

$execution = new Execution($one);
Tap::ok($one === $execution->node, 'at the start, on the first node');
$execution->event("continue");
Tap::ok($two === $execution->node, 'after continue, at the second node');

$execution->event("back");
Tap::ok($one === $execution->node, 'after back, on the first node again');

$execution->event("continue");
Tap::ok($two === $execution->node, 'after continue, on the second node again');

$execution->event("continue");
Tap::ok($three === $execution->node, 'after continue, on the third node');

