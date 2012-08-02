<?php
use Gaia\DB\Transaction;
use Gaia\DB\Connection;
use Gaia\Time;
use Gaia\Test\Tap;

if( ! isset( $seller_id ) ) $seller_id = uniqueUserId();
if( ! isset( $buyer_id ) ) $buyer_id = uniqueUserId();
if( ! isset( $item_id ) ) $item_id = uniqueNumber(1,100000);

Transaction::reset();
Connection::reset();

Transaction::start();
$souk = souk( $app, $seller_id);
$listing = $souk->auction( array( 'price'=>10, 'bid'=>0, 'item_id'=>$item_id) );
$read = $souk->get( $listing->id );
Tap::is( $listing, $read, 'new auction is readable before transaction is committed, while in the transaction');
Transaction::rollback();

Transaction::reset();
Connection::reset();


$read = $souk->get( $listing->id );
Tap::is($read, NULL, 'after transaction rollback, no entry found');
Transaction::reset();

Transaction::start();
$souk = souk( $app, $seller_id);
$listing = $souk->auction( array( 'price'=>10, 'bid'=>0, 'item_id'=>$item_id ) );

Time::offset( 86400 * 15 );
$listing = $souk->close( $listing->id);
Transaction::commit();

Tap::is( $listing->closed, 1, 'creating and closing a listing works inside a transaction');


unset ($seller_id );
unset( $buyer_id );
unset( $item_id );

//print "\n\n";