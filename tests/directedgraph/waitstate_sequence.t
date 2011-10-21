#!/usr/bin/env php
<?php

include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DirectedGraph\Node;
use Gaia\DirectedGraph\Execution;

Tap::plan(3);

// one --> two --> three
$one = new Node();
$two = new Node();
$three = new Node();
$one->add("continue",$two);
$two->add("continue",$three);

$execution = new Execution($one);
Tap::ok($one === $execution->node, 'execution node equals one');
$execution->event("continue");
Tap::ok($two === $execution->node, 'execution node equals two');
$execution->event("continue");
Tap::ok($three === $execution->node, 'execution node equals three');
