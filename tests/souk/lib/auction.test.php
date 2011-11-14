<?php
use Gaia\Test\Tap;
use Gaia\Time;
if( ! isset( $seller_id ) ) $seller_id = uniqueUserId();
if( ! isset( $buyer_id ) ) $buyer_id = uniqueUserId();
if( ! isset( $item_id ) ) $item_id = uniqueNumber(1,100000);

$souk = souk( $app, $seller_id );
$listing = $souk->auction( array( 'price'=>10, 'bid'=>0, 'item_id'=>$item_id) );
Tap::isa( $listing, 'Gaia\Souk\Listing', 'create returns a souk listing object');
Tap::is( $listing->price, 10, 'price returned is 10');
Tap::is( $listing->step, 1, 'step is one if no step specified');
Tap::is( $listing->bid, 0, 'opening bid is zero');
Tap::is( $listing->seller, $seller_id, 'seller matches the user id we passed in');
Tap::is( $listing->closed, 0, 'auction not closed yet');
Tap::is( $listing->buyer, 0, 'no buyer yet');
Tap::is( $listing->bidder, 0, 'no bidder either');
Tap::is( $listing->touch, 0, 'auction hasnt been touched yet');
Tap::cmp_ok( abs( $listing->created - Time::now() ), '<', 2, 'created matches current time');
Tap::is( $listing->expires - $listing->created, 86400 * 14, 'when no expires time set, defaults to 2 weeks');
Tap::is( $listing, $souk->get( $listing->id ), 'get by id returns same item we got back from auction step');
Tap::is( array($listing->id => $listing), $souk->fetch( array( $listing->id ) ), 'fetch by id returns same item we got back from auction step');

Time::offset( 86400 * 15 );
$listing = $souk->close( $listing->id);
Tap::isa( $listing, 'Gaia\Souk\Listing', 'close returns a souk listing object');
Tap::is( $listing->closed, 1, 'the listing is now closed');
Tap::cmp_ok( abs( $listing->touch - Time::now() ), '<', 2, 'after closing, touch matches current time');
Tap::is( $listing->buyer, 0, 'the buyer field is still zero');
Tap::is( $listing->bid, 0, 'bid is also empty');



$souk = Souk( $app, $seller_id );
$listing = $souk->auction( array( 'price'=>10, 'bid'=>0, 'item_id'=>$item_id ) );
$e = NULL;
try {
    $listing = $souk->bid($listing->id, 4);
} catch ( Exception $e ){
    $e = $e->getMessage();
}
Tap::is( $e, 'invalid bidder', 'sellers cant bid on their their own items');


$e = NULL;
try {
    $listing = $souk->buy($listing->id);
} catch ( Exception $e ){
    $e = $e->getMessage();
}
Tap::is( $e, 'invalid buyer', 'sellers cant buy their their own items');


$souk = Souk( $app, $buyer_id );
$listing = $souk->bid($listing->id, 2, array('enable_proxy'=>1));

Tap::is($listing->proxybid, 2, 'after bidding 2, proxybid amount is 2');
Tap::is( $listing->bid, 1, 'the current bid is 1');

Tap::is($listing->bidder, $buyer_id, 'bidder in listing matches bidder id');
Tap::is( $listing->price, 10, 'price for buy-now is 10');
Tap::is($listing->buyer, 0, 'still no buyer');
Tap::cmp_ok( abs( $listing->touch - Time::now() ), '<', 2, 'after bidding, touch matches current time');
$listing = $souk->bid($listing->id, 4, array('enable_proxy'=>1));

Tap::is( $listing->proxybid, 4, 'after bidding 4, the proxybid is now 4');
Tap::is( $listing->bid, 3, 'the actual winning bid is 3');


$listing = $souk->get( $listing->id);
Tap::is( $listing->bid, 3, 'a fresh read also confirms it');

$e = NULL;
try {
    $listing = $souk->bid($listing->id, 3);
} catch ( Exception $e ){
    $e = $e->getMessage();
}
Tap::is( $e, 'too low', 'when under-bidding, fails with message: too low');

$listing = $souk->close($listing->id);
Tap::is( $listing->bid, 3, 'after closing, bid is 3');
Tap::is( $listing->price, 10, 'after closing, price is 10 ... wasnt met');

Tap::is( $listing->closed, 1, 'auction is closed');
Tap::is( $listing->buyer, $listing->bidder, 'bidder is now the buyer');
Tap::cmp_ok( abs( $listing->touch - Time::now() ), '<', 2, 'after closing, touch matches current time');


$e = NULL;
try {
    $listing = $souk->close($listing->id );
} catch ( Exception $e ){
    $e = $e->getMessage();
}
Tap::is( $e, 'already closed', 'when trying to close an already closed listing, fails with message: already closed');



$e = NULL;
try {
    $listing = $souk->bid($listing->id, 4);
} catch ( Exception $e ){
    $e = $e->getMessage();
}
Tap::is( $e, 'closed', 'when bidding on a closed listing, fails with message: sold');




$e = NULL;
try {
    $listing = $souk->buy($listing->id);
} catch ( Exception $e ){
    $e = $e->getMessage();
}
Tap::is( $e, 'sold', 'when trying to buy a closed listing, fails with message: closed');



$e = NULL;
try {
    $listing = $souk->bid('20120111000000001', 1);
} catch ( Exception $e ){
    $e = $e->getMessage();
}
Tap::is( $e, 'not found', 'when bidding on a non-existent listing, fails with message: not found');



$souk = Souk( $app, $seller_id );
$listing = $souk->auction( array( 'price'=>10, 'item_id'=>$item_id) );

$souk = Souk( $app, $buyer_id );

$e = NULL;
try {
    $listing = $souk->bid($listing->id, 1);
} catch ( Exception $e ){
    $e = $e->getMessage();
}
Tap::is( $e, 'buy only', 'when bidding on buy-only, fails');

$listing = $souk->buy($listing->id);

Tap::is( $listing->closed, 1, 'buy works fine, tho');

$souk = Souk( $app, $seller_id );
$listing = $souk->auction( array( 'bid'=>0, 'item_id'=>$item_id ) );

$souk = Souk( $app, $buyer_id );

$e = NULL;
try {
    $listing = $souk->buy($listing->id);
} catch ( Exception $e ){
    $e = $e->getMessage();
}
Tap::is( $e, 'bid only', 'when buying bid-only, fails');


$listing = $souk->bid($listing->id, 1);

Tap::is( $listing->bid, 1, 'bid works fine, tho');


$listing = Souk( $app, $seller_id )->auction( array( 'bid'=>0, 'reserve'=>5, 'item_id'=>$item_id) );
$listing = Souk( $app, $buyer_id )->bid( $listing->id, 5);
Tap::is( $listing->bid, 5, 'without enable_proxy, bid is set, not stepped');
$listing = Souk( $app )->close( $listing->id );
Tap::is( $listing->buyer, 0, 'when reserve isnt met, bidder doesnt win listing');
Tap::is( $listing->closed, 1, 'even tho reserve wasnt met, closing still ends the bidding.');

unset ($seller_id );
unset( $buyer_id );
unset( $item_id );

Time::offset( 86400 * 30 );

$id = 0;
$ct = 0;
while( $listings = Souk( $app )->pending(0, 5, $id ) ){
    foreach( $listings as $id ){
        $ct++;
        //Tap::debug('pending: ' . $id );
    }
}
Tap::cmp_ok( $ct, '>=', 1, "found at least 1 item in pending: $ct found");
//print "\n\n";