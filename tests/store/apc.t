#!/usr/bin/env php
<?php

use Gaia\Test\Tap;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/apc_installed.php';


Tap::plan(11);

$apc = new Store\Apc;

$key = sha1('test' . microtime(TRUE) . __FILE__);

apc_store( $key, 1 );

Tap::cmp_ok( $apc->get( $key ), '===', 1, 'Write into apc and verify the store\apc class can read it');

$apc->set($key, 2);

Tap::cmp_ok(apc_fetch($key), '===', 2, 'change the value with store\apc and verify it changed in apc');

$apc->increment($key);

Tap::cmp_ok( apc_fetch($key), '===', 3, 'incremented the key and verified correct value in apc');


$apc->increment($key, 2);

Tap::cmp_ok( apc_fetch($key), '===', 5, 'incremented the key by several and verified correct value in apc');

$apc->decrement($key);

Tap::cmp_ok( apc_fetch($key), '===', 4, 'decremented the key and verified correct value in apc');


$apc->decrement($key, 2);

Tap::cmp_ok( apc_fetch($key), '===', 2, 'decremented the key by serveral and verified correct value in apc');

$apc->replace( $key, 100);

Tap::cmp_ok( apc_fetch( $key ), '===', 100, 'replaced the value and verified correct value shows up in apc');


$apc->delete($key);

Tap::cmp_ok(apc_fetch($key), '===', FALSE, 'delete the key using store\apc and verify it is gone');


$apc->replace( $key, 100);

Tap::cmp_ok( apc_fetch( $key ), '===', FALSE, 'attempted replace after delete and verified nothing shows up in apc');


$apc->add( $key, 100);

Tap::cmp_ok( apc_fetch( $key ), '===', 100, 'added the key after delete and verified it shows up in apc');

$apc->add( $key, 200);

Tap::cmp_ok( apc_fetch( $key ), '===', 100, 'added the key again with differrent value and verified nothing changes in apc');

