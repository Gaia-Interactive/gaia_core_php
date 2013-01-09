#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Test\Stub;

Tap::plan(12);



Tap::ok( $stub = new Stub( array() ), 'instantiated the stub class');

$errstr = '';
try { $stub->foo(); } catch( Exception $e ){ $errstr = $e->getMessage(); }

Tap::like($errstr, '#undefined#i', 'calling an undefined method triggers an exception');

$errstr = '';
try { $stub->foo; } catch( Exception $e ){ $errstr = $e->getMessage(); }

Tap::like($errstr, '#__get#i', 'accessing a property without defining a handler for __get triggers an exception');


$errstr = '';
try { $stub->foo = 'bar'; } catch( Exception $e ){ $errstr = $e->getMessage(); }

Tap::like($errstr, '#__set#i', 'assigning a property without defining a handler for __set triggers an exception');

$errstr = '';
try { isset( $stub->foo ); } catch( Exception $e ){ $errstr = $e->getMessage(); }

Tap::like($errstr, '#__get#i', 'checking if a property is set without defining a handler for __get or __isset triggers an exception');


$stub = new Stub( array(
    'foo'=>function(){ return 1000; },
));

Tap::is($stub->foo(), 1000, 'stubbed the foo function and got back the correct result');

$stub = new Stub( array(
    '__get'=>function($k){ if( $k == 'foo' ) return 2; throw new Exception('blow up'); },
));


Tap::is( $stub->foo, 2, 'after stubbing __get, accessing foo property returns correct result');
Tap::ok( isset( $stub->foo ), '__isset falls back on __get properly');

$stub = new Stub( array(
    '__isset'=>function($k){ if( $k == 'foo' ) return TRUE; throw new Exception('blow up'); },
));

Tap::is( isset( $stub->foo ), TRUE, '__isset handler works properly');

$stub = new Stub( array(
    '__set'=>function($k, $v){ return $v; },
));

Tap::is( $stub->foo = 25, 25, 'after stubbing __set, assigning foo property returns correct result');

if( error_get_last() ){
    Tap::ok(TRUE, 'cant test __unset, already been an error');
} else {
    unset( $stub->foo );
    Tap::is( error_get_last(), NULL, 'after stubbing __set, unset no longer alerts');
}


$stub = new Stub( array(
    '__unset'=>function($k){ if( $k == 'foo' ) return; throw new Exception('blow up'); },
));


if( error_get_last() ){
    Tap::ok(TRUE, 'cant test __unset, already been an error');
} else {
    unset( $stub->foo );
    Tap::is( error_get_last(), NULL, 'after stubbing __unset, unset no longer alerts');
}




