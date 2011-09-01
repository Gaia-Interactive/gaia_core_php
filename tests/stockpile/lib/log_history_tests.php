<?php

$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);

$stockpile = stockpile( $app, $user_id );
$rows = $stockpile->history();
Tap::is( $rows, array(), 'before adding an item, history returned a row-set with no rows');

// subtract on nothing, swallow errors.
try { $stockpile->subtract( $item_id ); } catch( Exception $e ) { }
$rows = $stockpile->history();
Tap::is( $rows, array(), 'after subtraction error because no items to remove, no logging took place');


$stockpile->add( $item_id );
$rows = $stockpile->history();
$row = $rows[0];
Tap::is( count( $rows ), 1, 'after adding an item, history returns a row-set with 1 row');
Tap::is( $row['item_id'], $item_id, 'item id in history matches what we added');
Tap::is( quantify( $row['quantity']), 1, 'quantity is 1');
Tap::is( $row['change'], 1, 'change is 1');
Tap::is( $row['txn'], '', 'no txn id provided since we didnt write inside a transaction');
Tap::cmp_ok( abs( $row['touch'] - Base::time() ), '<', 2, 'timestamp of touch seems reasonable, within a few seconds of now');

$stockpile->add( $item_id );
$rows = $stockpile->history();
$row = $rows[0];
Tap::is( count( $rows ), 2, 'after adding another item, history returns a row-set with 2 rows');
Tap::is( $row['item_id'], $item_id, 'item id in history matches what we added');
Tap::is( quantify($row['quantity']), 2, 'quantity is 2 because we started out with one.');
Tap::is( $row['change'], 1, 'change is 1');
Tap::is( $row['txn'], '', 'no txn provided since we didnt write inside a transaction');
Tap::cmp_ok( abs( $row['touch'] - Base::time() ), '<', 2, 'timestamp of touch seems reasonable, within a few seconds of now');

$stockpile->subtract( $item_id );
$rows = $stockpile->history();
$row = $rows[0];
Tap::is( count( $rows ), 3, 'after subtracting an item, history returns a row-set with 3 rows');
Tap::is( $row['item_id'], $item_id, 'item id in history matches what we subtracted');
Tap::is( quantify($row['quantity']), 1, 'quantity is now 1 because we had 2');
Tap::is( $row['change'], -1, 'change is -1');
Tap::is( $row['txn'], '', 'no txn provided since we didnt write inside a transaction');
Tap::cmp_ok( abs( $row['touch'] - Base::time() ), '<', 2, 'timestamp of touch seems reasonable, within a few seconds of now');

$txn = txn();
$stockpile = stockpile( $app, $user_id, $txn );
$stockpile->add( $item_id );
$rows = $stockpile->history();
Tap::is( count( $rows ), 3, 'after adding an item in a transaction but not committing it, no new row added to history');
$txn->commit();
$rows = $stockpile->history();
$row = $rows[0];
Tap::is( count( $rows ), 4, 'after committing txn, new row added to history');
Tap::ok( ctype_digit( $row['txn'] ), 'this time we have a txn id since we used a transaction');

$item_id = uniqueNumber(1, 1000000);
for( $i = 0; $i < 51; $i++ ) $stockpile->add( $item_id );
$txn->commit();
$rows = $allrows = $stockpile->history(array('item_id'=>$item_id));
Tap::is( count( $rows ), 50, 'add a bunch more items, rowset limited to 50');
$row = $rows[0];
Tap::ok( ctype_digit( $row['txn'] ), 'txn id showed up even with big batch operation');

$txn_id = $row['txn'];
$txncheck = TRUE;
foreach( $rows as $r ) {
    if( $r['txn'] != $txn_id ) {
        $txncheck = FALSE;
        //print_r( $rows );
        break;
    }
}
Tap::ok( $txncheck, 'all the items added have the same txn id');

$rows = $stockpile->history( array('item_id'=>$item_id, 'limit'=>5 ) );
Tap::is( count( $rows ), 5, 'grab only the last 5 rows of history');
Tap::is( $rows[0], $row, 'most recent addition is first');

// now grab the next 5 rows
$rows = $stockpile->history( array('item_id'=>$item_id, 'limit'=>5, 'offset'=>5 ) );
Tap::is( $rows, array_slice($allrows, 5, 5), 'paginate the result set into 5 rows, skipping ahead 5');

// search only by previous day
$rows = $stockpile->history( array('item_id'=>$item_id, 'daylimit'=>1, 'dayoffset'=>1) );
Tap::is( $rows, array(), 'search in the previous day');


$alt_item_id = uniqueNumber(1, 1000000);
$stockpile->add( $alt_item_id );
$stockpile->add( $item_id );
$txn->commit();
$rows = $stockpile->history(array('item_id'=>$alt_item_id));
Tap::is( count($rows), 1, 'add a new item  with a different item id and an old one ... and search for new one');
Tap::is( $rows[0]['item_id'], $alt_item_id, 'found it');




$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);
$txn = txn();
$stockpile = stockpile( $app, $user_id, $txn );

$stockpile->add( $item_id, 1, array('event'=>'halloween') );
$txn->commit();
$rows = $stockpile->history(array('limit'=>1));
Tap::is($rows[0]['event'], 'halloween', 'add an item with meta information. the information shows up in the log');

$stockpile->subtract( $item_id, 1, array('event'=>'xmas') );
$txn->commit();
$rows = $stockpile->history(array('limit'=>1));
Tap::is($rows[0]['event'], 'xmas', 'subtract an item with meta information. the information shows up in the log');


$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);
$txn = txn();
$stockpile = stockpile( $app, $user_id, $txn );

$stockpile->add( $item_id, 1, array('event'=> $teststring = 'äó') );
$txn->commit();
$rows = $stockpile->history(array('item_id'=>$item_id, 'limit'=>1));
Tap::is($rows[0]['event'], $teststring, 'utf8 characters come through');



$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);
$txn = txn();
$stockpile = stockpile( $app, $user_id, $txn );
for( $i = 0; $i < 20; $i++){
    $stockpile->add($item_id, $i + 1 );
    advanceCurrentTime( (3600 * 12) );
}

$txn->commit();

$diff = abs(count( $history = $stockpile->history() ) - 14);

Tap::cmp_ok($diff, '<', 2, 'after logging two entries a day over 10 days, we get back only 7 days worth of data');

$start = $history[0]['touch'];

$last_row = array_pop( $history );
$end = $last_row['touch'];
$diff = abs(floor(($start - $end)/ (3600 * 24)));

Tap::cmp_ok( $diff, '>', 5, 'the timestamps in the data span 6 or 7 days');




