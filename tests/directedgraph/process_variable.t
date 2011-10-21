#!/usr/bin/env php
<?php

include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DirectedGraph\Node;
use Gaia\DirectedGraph\Execution;

Tap::plan(3);

class ContextualExecution extends Execution {

    public $variables = array();

    public function __construct(Node $node) {
      parent::__construct($node);
    }
    
    public function setVariable($key, $value) {
        return $this->variables[ $key ] = $value;
    }
    
    public function getVariable( $key) {
      return isset( $this->variables[ $key ] ) ? $this->variables[ $key ] : NULL;
    }
}

$one = new Node();
$two = new Node();
$one->add("continue", $two);

$execution = new ContextualExecution($one);
$execution->setVariable("client", "coca-cola");
$execution->setVariable("product", "cans");
$execution->setVariable("amount", 10000);
$execution->event("continue");

Tap::ok("coca-cola" == $execution->getVariable("client"), 'client is coca-cola');
Tap::ok("cans" == $execution->getVariable("product"), 'product is cans');
Tap::ok(10000 == $execution->getVariable("amount"), 'amount is 10k');
