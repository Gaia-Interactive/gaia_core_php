<?php

use Gaia\Test\Tap;
use Gaia\DB\Transaction;

if( ! isset( $plan ) ) $plan = 0;

// how many tests are we gonna run?
Tap::plan(25 + $plan);

$name = 'name' . microtime(TRUE);
$id = strval( mt_rand(1, 100000000) );




$identifier = $create_identifier();
try { $identifier->store('12345a', $name); } catch( Exception $e ){ $e = $e->__toString();}
Tap::like($e, '#invalid-id#i', 'id must be number');
$e = null;
try { $identifier->store($id, ''); } catch( Exception $e ){ $e = $e->__toString();}
Tap::like($e, '#invalid-name#i', 'name must be at least 1 char');
$e = null;
$longname = '';
for( $i = 0; $i < 256; $i++) $longname .= 'a';
try { $identifier->store($id, $longname); } catch( Exception $e ){ $e = $e->__toString();}
Tap::like($e, '#invalid-name#i', 'name must be at less than 255 chars');
Tap::ok( $identifier->store($id, $name ), 'store the name/id pairing');
Tap::is( $identifier->byName($name), $id, 'found the id by name');
Tap::is( $identifier->byId($id), $name, 'found the name by id');
Tap::is( $identifier->byName(array($name)), array( $name=>$id), 'found the id by name in key/value pair format');
Tap::is( $identifier->byId(array($id)), array( $id=>$name ), 'found the name by id in key/value pair format'); 
$res = array('id'=>NULL, 'name'=>NULL);
$closure = function( $id, $name ) use( & $res ){
    $res['id'] = $id;
    $res['name'] = $name;
};
$identifier->batch( $closure, array('min'=>$id - 1, 'max'=>$id  + 1, 'limit'=>2 ));
Tap::is($res, array('id'=>$id, 'name'=>$name), 'Looped through the table, fetched back the row created');

Tap::ok( $identifier->store($id, $name ), 'able to overwrite with the same name');
$oldname = $name;
$name = 'name' . microtime(TRUE);
Tap::ok( $identifier->store($id, $name ), 'able to overwrite with the a new name');
Tap::is( $identifier->byName($name), $id, 'found the id by new name');
Tap::is( $identifier->byName($oldname), NULL, 'oldname doesnt match anything now');
$oldid = $id;
$id = strval( mt_rand(1, 100000000) );
Tap::ok( $identifier->store($id, $name ), 'able to overwrite name with the a new id');
Tap::is( $identifier->byId($id), $name, 'found the name by new id');
Tap::is( $identifier->byID($oldid), NULL, 'oldid doesnt match anything now');
$e = null;
try { $identifier->store(strval( mt_rand(1, 100000000) ), $name, $strict = TRUE); } catch( Exception $e ){ $e = $e->__toString();}
Tap::like($e, '#name-taken#i', 'trying to map name to new id with strict clause thrown in fails');
Tap::ok( $identifier->delete( $id, $name ), 'deleted the pairing');
Tap::is( $identifier->byName($name), NULL, 'after deleting, no name lookup');
Tap::is( $identifier->byId($id), NULL, 'after deleting, no id lookup');
Transaction::start();
$identifier = $create_identifier();
Tap::ok( $identifier->store($id, $name ), 'store the name/id pairing with txn');
Tap::is( $identifier->byName($name), $id, 'found the id by name');
Tap::is( $identifier->byId($id), $name, 'found the name by id');
Transaction::rollback();
Transaction::reset();
$identifier = $create_identifier();
Tap::is( $identifier->byName($name), NULL, 'after rollback, no id found by name');
Tap::is( $identifier->byId($id), NULL, 'after rollback, no name found by id');

