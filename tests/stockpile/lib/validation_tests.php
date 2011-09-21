<?php

namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);

// get back same app name
Tap::is( stockpile( $app, $user_id )->app(), $app, 'app returns same value as passed in');

// test app name uppercase
$e = NULL;
try { stockpile('TEST', $user_id ); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid app/', $e->getMessage()), 'cant use uppercase app name');

// test app name with a dash
$e = NULL;
try { stockpile('t-est', $user_id ); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid app/', $e->getMessage()), 'cant use dash in app name');

// user returns same value as passed in
Tap::is( stockpile( $app, $user_id )->user(), $user_id, 'user returns same value as passed in');


// make sure user is a number
$e = NULL;
try { stockpile($app, 'abc' ); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid user/', $e->getMessage()), 'user id must be a number');

$e = NULL;
try { stockpile($app, -1 ); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid user/', $e->getMessage()), 'user id must be a positive int');

$e = NULL;
try { stockpile($app, 0 ); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid user/', $e->getMessage()), 'user id cant be zero');


$e = NULL;
try { stockpile($app, '01' ); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid user/', $e->getMessage()), 'user id cant have leading zeros');

$e = NULL;
try { stockpile($app, $user_id )->get('a'); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid item/', $e->getMessage()), 'get item id must be a number');

$e = NULL;
try { stockpile($app, $user_id )->get('001'); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid item/', $e->getMessage()), 'get item id cant have leading zeros');

$e = NULL;
try { stockpile($app, $user_id )->get('-1'); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid item/', $e->getMessage()), 'get item id must be a positive number');

$e = NULL;
try { stockpile($app, $user_id )->get(array('-1')); } catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/invalid item/', $e->getMessage()), 'get item id must be a positive number when passed in as a multi-get too');
