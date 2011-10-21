#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DirectedGraph\Node;
use Gaia\DirectedGraph\Execution;

Tap::plan(4);

// one --> two --> three
//  |       |
//  +---<---+

class TestCallee {
    public $invoke = 0;
    public function run(){ $this->invoke++; }
}

$callee = new TestCallee;
$action_callee = new TestCallee;
$acb = array($action_callee, 'run');
$one = new Node( array( $callee, 'run') );
$two = new Node( array( $callee, 'run') );
$one->add('leave-node', $acb);
$two->add('enter-node', $acb);
$one->add("continue",$two);

$execution = new Execution($one);
Tap::ok($one === $execution->node, 'at the start, on the first node');
$execution->event("continue");
Tap::ok($two === $execution->node, 'after continue, at the second node');
Tap::ok($callee->invoke == 1, 'after continue, the callee has been invoked once');
Tap::ok($action_callee->invoke == 2, 'two action callbacks have been fired');
