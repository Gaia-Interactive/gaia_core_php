#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DirectedGraph\Node;
use Gaia\DirectedGraph\Execution;

class TestAction {

    public static $messages = array();
    
    public static function burnEvidence(){
        self::$messages[] = 'burn any paper evidence';
    }
    
    public static function orderKleenex(){
        self::$messages[] = 'order more kleenex';
    }
    
    public static function fixRoof(){
        self::$messages[] = 'fix the hole in the roof';
    }
}


//            __ payraise 
//           /
// evaluation
//           \__ fire

// setting up the graph structure
$evaluation = new Node();
$payraise = new Node();
$fire = new Node();
$evaluation->add("positive", $payraise);
$evaluation->add("negative", $fire);

// adding 2 "invisible" actions
// invisible means that the actions are not part of the 
// process graph.  it is the process graph that serves as 
// the basis for the graphical representation.

// after an evaluation, the paper evidence should be burned, whatever the 
// outcome of the evaluation.
$evaluation->add("leave-node", 'TestAction::burnEvidence');

// only on the negative event, order more kleenex
$evaluation->add("negative", 'TestAction::orderKleenex');

// only on the positive event, fix the hole in the roof
$evaluation->add("positive", 'TestAction::fixRoof');

TestAction::$messages = array();
$execution = new Execution($evaluation);
$execution->event("positive");
$expected_messages = array("fix the hole in the roof","burn any paper evidence");
Tap::ok($expected_messages == TestAction::$messages, 'positive evaluation works');

TestAction::$messages = array();
$execution = new Execution($evaluation);
$execution->event("negative");
$expected_messages = array("order more kleenex","burn any paper evidence");
Tap::ok($expected_messages == TestAction::$messages, 'negative evaluation works');
