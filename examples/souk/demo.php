<?php
    include __DIR__ . '/../common.php';
    use Gaia\Souk;
    use Gaia\DB;
    
    DB\Connection::load( array('test'=> function(){ return new DB\Driver\PDO( 'sqlite:/tmp/souk.db'); }));
    Gaia\Souk\Storage::attach( function ( Gaia\Souk\Iface $souk ){ return 'test';} );
    Gaia\Souk\Storage::enableAutoSchema();
    
    try {
        $app = 'test1';
        $seller_id = mt_rand(1, 1000000000);
        $buyer_id = mt_rand(1, 1000000000);
        $souk = new Souk( $app, $seller_id);
        $listing = $souk->auction( array('price'=>10, 'item_id'=>1, 'quantity'=>1, 'bid'=>2, 'step'=>1) );
        print "\nNew auction:\n";
        print_r( $listing->export() );
        $souk = new Souk( $app, $buyer_id);
        $listing = $souk->bid($listing->id, 10);
        print "\nAfter first bid:\n";
        print_r( $souk->get( $listing->id )->export() );
        $souk = new Souk( $app );
        $listing = $souk->close($listing->id);
        print "\nAfter closing:\n";
        print_r( $listing->export() );
        $souk = new Souk( $app, $seller_id );
        $listing = $souk->auction( array('item_id'=>1, 'quantity'=>1, 'price'=>10) );
        print "\nNew buy-only auction:\n";
        print_r( $listing->export() );
        $souk = new Souk( $app, $buyer_id);
        $listing = $souk->buy( $listing->id );
        print "\nAfter buying:\n";
        print_r( $listing->export() );
        $souk = new Souk( $app, $seller_id );
        $ids = array();
        $now = time();
        for( $i = 0; $i < 10; $i++){
            $listing = $souk->auction( array('item_id'=>1, 'price'=>10+$i, 'expires'=>$now + 86400 + $i) );
            $ids[] = $listing->id;
        }
        print "\ncreate a bunch of auctions and get them all at once\n";
        print_r( $souk->fetch( $ids ) );    
        print "\nSearch for the items we created\n";
        $search_options = array( 'seller'=>$seller_id,'closed'=>0, 'sort'=>'expires_soon', 'floor'=>11, 'ceiling'=>15);
        $ids =  $souk->search( $search_options );
        print_r( $souk->fetch( $ids ) );
        foreach( $ids as $id ) $souk->close( $id );
     
    } catch( Exception $e ){
        print $e;
    }
    
