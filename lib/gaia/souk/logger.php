<?php
namespace Gaia\Souk;
use Gaia\DB\Transaction;

/**
 * @package GAIAONLINE
 * @copyright 2003-present GAIA Interactive, Inc.
 * @license private/license/GAIAONLINE.txt
 */

class Logger extends Passthru {
    
    public function auction( $l, array $data = NULL ){
        $listing = $this->core->auction( $l, $data );
        $this->log( $listing, __FUNCTION__ );
        return $listing;
    }
    public function close( $id, array $data = NULL ){
        $listing = $this->core->close( $id, $data );
        $this->log( $listing );
        return $listing;
    }
    public function buy($id, array $data = NULL ){
        $listing = $this->core->buy( $id, $data );
        $this->log( $listing, __FUNCTION__ );
        return $listing;
    }
    public function bid( $id, $bid, array $data = NULL ){
        $listing = $this->core->bid( $id, $bid, $data );
        $this->log( $listing, __FUNCTION__ );
        return $listing;
    } 
    
    protected function log( Souk_Listing $listing, $action ){
        $attributes = array();
        foreach( array_diff( $listing->keys(), Souk_Util::fields() ) as $k ){
            if( $k == 'id' ) continue;
            $attributes[ $k ] = $listing->$k;
        }
        list( $shard, $row_id ) = Souk_Util::parseId( $listing->id, TRUE);
        
        $db = Transaction::instance('souk');
        $sql = "insert into $table (rowid, action, seller, created, expires, closed, buyer, bidder, bidcount, touch, price, pricesort, item_id, bid, step, quantity, attributes ) values ( %i, %s, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %s )";
        $rs = $db->query( $sql,
                $row_id,
                $action,
                $listing->seller,
                $listing->created,
                $listing->expires,
                $listing->closed,
                $listing->buyer,
                $listing->bidder,
                $listing->bidcount,
                $listing->price,
                ceil( $listing->price / $listing->quantity ),
                $listing->item_id,
                $listing->bid,
                $listing->quantity,
                $listing->bid,
                $listing->step,
                $listing->quantity,
                json_encode($attributes));

        if( ! $rs ) {
            throw new Exception('database error', $db);
        }
    }
}
// EOF
