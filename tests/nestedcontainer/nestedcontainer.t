#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\NestedContainer;


Tap::plan(22);

$array = array(1,2,3);
$c = new NestedContainer($array);
Tap::ok($c instanceof NestedContainer, 'instantiated new nested container with array');
Tap::ok(is_scalar($c->current()), 'got back expected scalar value');
Tap::is($c->all(), $array, 'result from all() is equal to initial value');


$array = array(1 => array(1,2,3), 2 => 'foo');
$c = new NestedContainer($array);
Tap::ok($c instanceof NestedContainer, 'instantiated new nested container with array of arrays');
Tap::ok($c->get(1) instanceof NestedContainer, 'nested array value is also NestedContainer type');
Tap::ok(is_scalar($c->get(2)), 'scalar associative mixed with nested is still scalar');
Tap::is($c->all(), $array, 'result from all() is equal to initial value');

$array =    array('deep' => 
            array('nest' => 
            array('is' => 
            array('very' => 
            array('deep' => 
            array('deep' => 
            array('deepest' =>  'scalar')))))),
            'derp' => 'derpyvalue');
$c = new NestedContainer($array);
Tap::ok($c instanceof NestedContainer, 'instantiated new nested big deep nesting');
Tap::ok($c->deep instanceof NestedContainer, '1 level nesting');
Tap::ok($c->deep->nest instanceof NestedContainer, '2 level nesting');
Tap::ok($c->deep->nest->is instanceof NestedContainer, '3 level nesting');
Tap::ok($c->deep->nest->is->very instanceof NestedContainer, '4 level nesting');
Tap::ok($c->deep->nest->is->very->deep instanceof NestedContainer, '5 level nesting');
Tap::ok($c->deep->nest->is->very->deep->deep instanceof NestedContainer, '5 level nesting plus duplicate keys');
Tap::is($c->deep->nest->is->very->deep->deep->deepest, 'scalar', 'find scalar at bottom of nesting');
Tap::is($c->derp, 'derpyvalue', 'find the derp');
Tap::ok($c->get('deep')->get('nest')->get('is')->get('very') instanceof NestedContainer, 'use get() instead of -> notation');
Tap::is($c->all(), $array, 'result from all() is equal to initial value');

class NotContainer{
    public $dont_taze_me = 'bro'; 
    public function foo(){ return 'bar'; }
}

$not_container = new NotContainer();

$array = array('foo' => array('bar'), 'not' => $not_container, 'scalar' => 1);
$c = new NestedContainer($array);
Tap::ok($c instanceof NestedContainer, 'instantiated new mixed container');
Tap::ok($c->foo instanceof NestedContainer, 'find the nested one');
Tap::is($c->foo->current(), 'bar', 'find nested value with next() on indexed array');
Tap::ok($c->not instanceof NotContainer, 'non container does not get turned into container');
Tap::ok(is_scalar($c->scalar), 'scaler is still scaler');
Tap::is($c->not->foo(), 'bar', 'non container object still works');
Tap::is($c->not->dont_taze_me, 'bro', 'bro got tazed');

class ExtendedNestedContainer extends NestedContainer{
    public function foo(){return 'bar';}
}

$c = new ExtendedNestedContainer($array);

Tap::ok($c instanceof NestedContainer, 'instantiated new mixed ExtendedNestedContainer');
Tap::ok($c->foo instanceof NestedContainer, 'find the nested one');
Tap::is($c->foo->current(), 'bar', 'find nested value with next() on indexed array');
Tap::ok($c->not instanceof NotContainer, 'non container does not get turned into container');
Tap::ok(is_scalar($c->scalar), 'scaler is still scaler');
Tap::is($c->not->foo(), 'bar', 'non container object still works');
Tap::is($c->not->dont_taze_me, 'bro', 'bro got tazed');
Tap::is($c->foo(), 'bar', 'extnded class method works');






