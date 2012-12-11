<?php

use Gaia\Test\Tap;
use Gaia\affiliate;


Tap::plan(14);

Tap::ok($affiliate instanceof affiliate\Iface, 'object implements the affiliate interface');

$identifiers = array(
    'a' . microtime(TRUE) . '.' . mt_rand(),
    'b' . microtime(TRUE) . '.' . mt_rand(),
    'c' . microtime(TRUE) . '.' . mt_rand(),
);

$res = $affiliate->join( $identifiers );

$affiliate_id = $res[$identifiers[0]];

Tap::is( $res, array_fill_keys( $identifiers, $affiliate_id), 'ran the join command and got back a key list of identifiers mapping to my affiliate id');

Tap::ok( ctype_digit( $affiliate_id ), 'affiliate id is a string of digits');

Tap::is( $affiliate->related( $identifiers ), $res, 'affiliate::related() returns same response as join did');

$identifiers[] = $last = 'd' . microtime(TRUE) . '.' . mt_rand();

$res = $affiliate->join( $identifiers );

Tap::is( $res, array_fill_keys( $identifiers, $affiliate_id), 'join a new identifier to the group');

Tap::is( $affiliate->related( $identifiers ), $res, 'affiliate::related() returns same response as join did');

Tap::is( $affiliate->identifiers( array($affiliate_id) ), array( $affiliate_id=>$identifiers ), 'affiliate::get returns identifiers mapped to the affiliate id');


$unrelated = 'd' . microtime(TRUE) . '.' . mt_rand();

$res = $affiliate->join( array( $unrelated ) );

$new_affiliate_id = $res[$unrelated];

Tap::is( $affiliate->identifiers( array( $new_affiliate_id ) ), array( $new_affiliate_id=>array( $unrelated ) ), 'joining a single identifer, creates an orphaned new affiliateid');
Tap::isnt( $affiliate_id, $new_affiliate_id, 'old and new affiliate ids are different');

$res = $affiliate->join( array( $last, $unrelated ) );

Tap::is( $res, array_fill_keys( array_merge( $identifiers, array($unrelated) ), $affiliate_id), 'joined unrelated to the other identifiers');

Tap::is( $affiliate->related( $identifiers ),  array_fill_keys( array_merge( $identifiers, array($unrelated) ), $affiliate_id), 'the new identifer is now related to the rest');

$affiliate->delete( $identifiers );

Tap::is( $affiliate->related( $identifiers ),  array_fill_keys($identifiers, NULL) , 'after deleting the identifiers, no associations');

Tap::is( $affiliate->related( array( $unrelated ) ),  array_fill_keys(array($unrelated), $affiliate_id), 'the one identifier we didnt delete still remains');

$affiliate->delete(  array( $unrelated ) );

Tap::is( $affiliate->related( array( $unrelated ) ),  array_fill_keys(array($unrelated), NULL), 'deleted the last identifier');

