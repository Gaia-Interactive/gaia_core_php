#!/usr/bin/env php
<?php

include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DirectedGraph\Node;
use Gaia\DirectedGraph\Execution;

Tap::plan(2);

function synchronize_it(Execution $execution) { $execution->event("continue"); }


// one --> two --> three --> four
$one = new Node();
$two = new Node('synchronize_it');
$three = new Node('synchronize_it');
$four = new Node();
$one->add("continue",$two);
$two->add("continue",$three);
$three->add("continue",$four);

$execution = new Execution($one);
Tap::ok($one === $execution->node, 'starting at node one');
$execution->event("continue");
Tap::ok($four === $execution->node, 'end up at node four');

// EOF