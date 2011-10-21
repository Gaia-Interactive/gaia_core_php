#!/usr/bin/env php
<?php

include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DirectedGraph\Node;
use Gaia\DirectedGraph\Execution;
use Gaia\Container;

Tap::plan(9);

class ExecutionPath extends Execution {
    public $name;
    public $parent = null;
    public $children = array();
    public $isActive = TRUE;

    public function __construct($name, Node $node) {
      parent::__construct($node);
      $this->name = $name;
    }
    
    public function createChild($name) {
      $child = new self($name, $this->node);
      $child->parent = $this;
      $this->children[ $name ] = $child;
      return $child;
    }
    
    public function end() {
        $this->isActive = false;
    }
}


  
function fork_it( ExecutionPath $execution) {
    foreach( array_keys( $execution->node->transitions ) as $event) $execution->createChild($event);
    foreach( $execution->children as $child ) $child->event($child->name);
}



/** join node implementation.  aka and-merge */
class Join extends Node {
    
    public $parentReactivationEvent = null;

    /** a join must have exactly 1 leaving transition */
    public function add($event, $destination) {
        if (count($this->transitions) == 1) {
            throw new Exception("a join must have exactly 1 transition");
        }
        parent::add($event, $destination);
        $this->parentReactivationEvent = $event;
    }
    
    public function execute(ExecutionPath $execution) {
        // mark the execution path that arrives in this join as 'ended'
        $execution->end();
        $hasActiveSibling = false;
      
        // create child execution paths
        foreach($execution->parent->children as $sibling) {
            if (! $sibling->isActive) continue;
            $hasActiveSibling = true;
            break;
        }
      
        if (!$hasActiveSibling) {
            // reactivate parent 
            // the parent was waiting in the fork.
            // the parent will now resume execution starting 
            // from this join
            $execution->parent->node = $this;
            $execution->parent->event($this->parentReactivationEvent);
        }
    }
}






//           _bill_
//          /      \
// sell-->fork    join-->done
//          \_ship_/

$sell = new Node();
$fork = new Node('fork_it');
$bill = new Node();
$ship = new Node();
$join = new Join();
$done = new Node();
$sell->add("order placed", $fork);
$fork->add("billing", $bill);
$fork->add("shipping", $ship);
$bill->add("billing complete", $join);
$ship->add("shipping complete", $join);
$join->add("continue", $done);

$main = new ExecutionPath("main", $sell);
$main->event("order placed");

// now, we should have following tree of execution paths:
//         main
//         / \
//  billing  shipping

$billing = $main->children["billing"];
$shipping = $main->children["shipping"];
Tap::ok($fork === $main->node, 'fork equals main node');
Tap::ok($bill === $billing->node, 'bill equals billing node');
Tap::ok($ship === $shipping->node, 'ship equals shipping node');

// let's suppose that billing completes first
$billing->event("billing complete");

Tap::ok($fork === $main->node, 'fork equals main node' );
// then the billing will wait in the join and 
// the main path of execution remains in the fork
Tap::ok($join === $billing->node, 'join equals billing node');
Tap::ok($ship === $shipping->node, 'ship equals shipping node');

// but when shipping also complets...
$shipping->event("shipping complete");

// the main path of execution will have been
// reactivated from the join and it will 
// have entered the node 'done'
Tap::ok($done === $main->node, 'main node is done');
// both billing and shipping will be ended and 
// remain positioned in the join
Tap::ok($join === $billing->node, 'billing node is join');
Tap::ok($join === $shipping->node, 'shipping node is join');