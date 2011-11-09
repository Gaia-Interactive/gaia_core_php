#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';


Tap::plan(6);

$db = new DB\Callback( array('execute'=>function($query){
    return new DB\StaticResult( array(array('foo'=>'dummy\'', 'bar'=>'rummy')  ) );
}));

$fd = fopen('php://memory', 'w+');

$a = null;
$r = NULL;
$o = new DB\Observe( $db, array('execute'=>
    function( $args, $result ) use ( &$a, &$r, $fd) {
        $a = $args;
        $r = $result;
        fwrite( $fd, 'db query: ' . $args[0]);
    }
));


$rs = $o->execute('SELECT %s as foo, %s as bar', 'dummy\'', 'rummy');
Tap::ok( $rs, 'query executed successfully');
Tap::is( $rs, $r, 'result object passed back to the callback');
Tap::is( $a, array( 'SELECT %s as foo, %s as bar', 'dummy\'', 'rummy'), 'got method args passed to the callback too');

Tap::is( $o->isa('Gaia\DB\Observe'), TRUE, 'wrapper tells us isa about itself');
Tap::is( $o->isa('Gaia\DB\Callback'), TRUE, 'wrapper tells us the core instanceof');
Tap::is( $o->isa('Gaia\DB\Transaction'), FALSE, 'doesnt false report instanceof');
