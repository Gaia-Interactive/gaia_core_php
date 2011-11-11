<?php
namespace Gaia\Souk\Storage;
use \Gaia\DB\Driver;
use \Gaia\Exception;
use \Gaia\Store;
use \Gaia\Souk;
use \Gaia\DB\Transaction;

class SQLite implements IFace {

    protected $db;
    protected $app;
    protected $user_id;
    protected $dsn;
    public function __construct( \Gaia\DB $db, $app, $user_id, $dsn){
        if( ! $db->isa('sqlite') ) throw new Exception('invalid driver', $db );
        $this->db = $db;
        $this->app = $app;
        $this->user_id = $user_id;
        $this->dsn = $dsn;
        

    }
    
    protected function create($table){
        $cache = \Gaia\Souk\Storage::cacher();
        $key = 'souk/storage/__create/' . md5( $this->dsn . '/' . get_class( $this ) . '/' . $table );
        if( $cache->get( $key ) ) return;
        if( ! $cache->add( $key, 1, 60 ) ) return;
        
        $rs = $rs = $this->execute("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' and `name` = %s", $table);
        $row = $rs->fetch();
        $rs->free();
        if( ! $row ) {
             $this->execute(
            "CREATE TABLE IF NOT EXISTS $table (
              `row_id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `seller` BIGINT NOT NULL,
              `buyer` BIGINT NOT NULL DEFAULT '0',
              `item_id` INTEGER NOT NULL DEFAULT '0',
              `quantity` BIGINT NOT NULL DEFAULT '0',
              `price` BIGINT default '0',
              `pricesort` BIGINT default '0',
              `step` BIGINT default '0',
              `bid` BIGINT default '0',
              `proxybid` BIGINT default '0',
              `bidcount` INTEGER default '0',
              `bidder` BIGINT default '0',
              `reserve` BIGINT default NULL,
              `closed` INTEGER NOT NULL DEFAULT '0',
              `created` INTEGER,
              `expires` INTEGER,
              `touch` INTEGER
            )"
                    
            );
            
            foreach( array('closed', 'created', 'expires', 'pricesort', 'item_id', 'step', 'seller', 'bidder', 'buyer') as $idx ){
                $idx_name = $table . '_idx_' . $idx;
                $this->execute("CREATE INDEX IF NOT EXISTS `$idx_name` ON `$table` (`$idx`)");
            }
        }
       
        $table_attr = $table .'_attr';
        
        $rs = $this->execute("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' and `name` = %s", $table_attr);
        $row = $rs->fetch();
        $rs->free();
        if( ! $row ) {
             $this->execute(
            "CREATE TABLE IF NOT EXISTS $table_attr (
              `row_id` INTEGER NOT NULL,
              `attributes` TEXT,
              PRIMARY KEY  (`row_id`)
            )"
            );
        }
        
    }
    
    public function buy( $listing ){
            
            // extract shard and row id from the id
            list( $shard, $row_id ) = Souk\Util::parseId( $listing->id );
            
            $table = $this->table( $shard );
            
            if( \Gaia\Souk\Storage::isAutoSchemaEnabled() ) $this->create( $table );

            $sql = "UPDATE $table SET `buyer` = %i, `touch` = %i, `closed` = 1, `pricesort` = NULL WHERE `row_id` = %i AND closed = 0";
            if( ! Transaction::atStart() ) Transaction::add( $this->db );
            $rs = $this->execute($sql, $listing->buyer, $listing->touch, $row_id);
            
            // should have affected 1 row. if it didn't something is wrong.
            if( $rs->affected() < 1 ) throw new Exception('failed', $this->db );
    }
    
        /**
    * create a new listing in the dao.
    * doesn't do any of the sanitization or validation, just
    * does the raw heavy lifting of assigning all the values to the dao and running the query.
    */
    public function createListing( Souk\Listing $listing ){

        // grab all the fields passed in that don't map to pre-defined fields.
        $attributes = array();
        foreach( array_diff( $listing->keys(), Souk\Util::fields() ) as $k ){
            if( $k == 'id' ) continue;
            $attributes[ $k ] = $listing->$k;
        }
        
        $shard = Souk\Util::dateshard()->shard();        
        $table = $this->table($shard);
        
        if( \Gaia\Souk\Storage::isAutoSchemaEnabled() ) $this->create( $table );
        
        if( ! Transaction::atStart() ) Transaction::add( $this->db );
        
        $sql = "INSERT INTO $table
        (seller, created, expires, closed, buyer, bidder, bidcount, touch, price, pricesort, item_id, bid, step, reserve, quantity) 
        VALUES 
        (%i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i)";
        $rs = $this->execute($sql,
                $listing->seller,
                $listing->created,
                $listing->expires,
                $listing->closed,
                $listing->buyer,
                $listing->bidder,
                $listing->bidcount,
                $listing->touch,
                $listing->price,
                Souk\Util::calcPriceSort( $listing->price, $listing->quantity ),
                $listing->item_id,
                $listing->bid,
                $listing->step,
                $listing->reserve,
                $listing->quantity
                );
        $row_id = $rs->insertId();
        $listing->id = Souk\Util::composeId( $shard,  $row_id);
        
        if( $attributes ){
            $table_attr = $table . '_attr';
            $this->execute("INSERT OR IGNORE INTO `$table_attr` (`row_id`, `attributes`) VALUES (%i, %s)", $row_id, '');
            $this->execute( "UPDATE `$table_attr` SET `attributes` = %s WHERE `row_id` = %i", json_encode( $attributes ), $row_id );
        }
    }
    
    public function pending( $age = 0, $limit = 1000, $offset_id = 0){
        $ts = Souk\Util::now() - $age;        
        $list = array();
        $offset_row_id = $offset_shard = 0;
        if( $offset_id ){
            list( $offset_shard, $offset_row_id ) = Souk\Util::parseId( $offset_id );
        }
        
        $shards = array();
        $ds = Souk\Util::dateshard();
        foreach( $ds as $shard ){
            if( $offset_shard && $offset_shard > $shard ) continue;
            $shards[] = $shard;
        }
        $shards = array_reverse( $shards );
        
        foreach( $shards as $shard ){
            if( $limit < 1 ) break;
            $table = $this->table( $shard );
            if( \Gaia\Souk\Storage::isAutoSchemaEnabled() ) $this->create( $table );
            $sql = "SELECT row_id FROM $table WHERE row_id > %i AND closed = 0 AND expires < %i ORDER BY expires ASC LIMIT %i";
            //print "\n" . $this->db->prep( $sql, $offset_row_id, $ts, $limit );
            $rs = $this->execute($sql, $offset_row_id, $ts, $limit );
            $offset_row_id = 0;
            while( $row = $rs->fetch() ) {
                $list[] = Souk\Util::composeId( $shard, $row['row_id'] );
                $limit--;
                if( $limit < 1 ) break;
            }
            $rs->free();
            
        }
        return $list;
    }
    
    public function bid( $listing ){
            // extract shard and row id from the souk id.
            list( $shard, $row_id ) = Souk\Util::parseId( $listing->id );
            
            // update the listing with the bidder's info. 
            if( ! Transaction::atStart() ) Transaction::add( $this->db );
            
            // create the table name
            $table = $this->table($shard);
            
            $sql = "UPDATE $table SET bid = %i, proxybid = %i, pricesort = %i, bidder = %i, touch = %i, bidcount = %i WHERE row_id = %i";
            $rs = $this->execute( $sql,
                    $listing->bid, 
                    $listing->proxybid, 
                    Souk\Util::calcPriceSort( $listing->bid, $listing->quantity ),
                    $listing->bidder,
                    $listing->touch,
                    $listing->bidcount,
                    $row_id
                    );
            
            // should have affected 1 row. if not, blow up.
            if( $rs->affected() < 1 ) {
                throw new Exception('failed', $db );
            }
    
    }
    
   /**
    * Get multiple listings back, by id
    * @return array
    */
    public function fetch( array $ids, $lock = FALSE){
        // stub out the result set so when we populate the data later, we will be 
        // returning the rows in the correct order as requested.
        // important when we do things like pass the list of ids from search into here,
        // where the ordering of ids matters.
        $result = array_fill_keys($ids, NULL);
        
        // we have to query by shard, which means we need to parse all the ids passed in
        // and group them by shard.
        $shardlist = array();
        foreach( $ids as $id ){
            list( $shard, $row_id ) = Souk\Util::parseId( $id );
            if( ! isset( $shardlist[ $shard ] ) ) $shardlist[ $shard ] = array();
            $shardlist[ $shard ][] = $row_id;
        }
        
        if( $lock && ! Transaction::atStart() ) Transaction::add( $this->db );
        
        // loop through the ids grouped by shard
        foreach( $shardlist as $shard => $row_ids ){
            // query always by shard and row_id. this query is a primary key lookup 
            // so it should always be very efficient.
            $table = $this->table($shard );
            
            
            if( \Gaia\Souk\Storage::isAutoSchemaEnabled() ) $this->create( $table );
        
            $sql = "SELECT row_id, bid, proxybid, bidcount, item_id, quantity, price, step, created, expires, buyer, seller, bidder, reserve, closed, touch FROM $table WHERE row_id IN (?)";
            $rs = $this->execute($sql, $row_ids);
            
            // grab the rows returned and populate the result as Souk\listings.
            $row_ids = array();
            while(  $row = $rs->fetch() ){
                $row_id = $row_ids[] = $row['row_id'];
                unset( $row['row_id'] );
                $listing = Souk\Util::listing( $row );
                $listing->id = Souk\Util::composeId( $shard, $row_id );
                $result[ $listing->id ] = $listing;
            }
            $rs->free();
            
            // did we get any rows back? if not, move on to the next shard.
            if( count( $row_ids ) < 1 ) continue;
            
            // populate the listing with the serialized attributes that didn't map to any of
            // the predefined columns in souk. in most cases this table will be empty, but
            // we query it anyway to be sure.
            // don't need a row lock on this table because we have one on the main listing table.
            // since we always access these two tables in tandem, the prior row lock should 
            // serialize the requests and work for both.
            $table_attr = $table . '_attr';
            $sql = "SELECT row_id, attributes FROM $table_attr WHERE row_id IN ( %i )";
            $rs = $this->execute($sql, $row_ids);
            
            // no rows? no problem, just skip it. that is expected.
            if( $rs->affected() < 1 ) continue;
            
            // someone stored attributes! merge them in.
            // stored as json in the db. deserialize and layer on top of the listing object.
            while( $row = $rs->fetch() ){
                $id = Souk\Util::composeId( $shard, $row['row_id'] );
                if( ! isset( $result[ $id ] ) ) continue;
                $attributes = json_decode($row['attributes'], TRUE);
                if( !is_array( $attributes ) ) continue;
                $listing = $result[ $id ];
                foreach( $attributes as $k => $v ){
                    if( $k == 'id' ) continue;
                    $listing->$k = $v;
                }
            }
            // free the query result.
            $rs->free();
            
        }
        
        // remember how we populated with nulls at the beginning? 
        // remove any that are still null, now that we are done.        
        foreach( array_keys( $result, NULL, TRUE) as $k) {
            unset( $result[ $k ] );
        }
        
        // return the result.
        return $result;
    }
    
    public function search( $options ){
        // we query the listings table which is sharded by month.
        // because it is sharded by month, we have to do a few more tricks
        // to get the data-set we want.
        // first, we try to narrow it down as much as possible by the criteria specified.
        
        $clauses = array();
        
        
        // are we looking for auctions that are still in progress or already finished?
        $clauses[] = $this->db->prep('closed = ?', $options->closed ? 1 : 0);
        
        // if we have a specific item id we are looking for, query for that.
        if( isset( $options->item_id ) ) $clauses[] = $this->db->prep('item_id = %i', $options->item_id );
        
        // do we know the seller?
        if( isset( $options->seller ) ) $clauses[] = $this->db->prep('seller = %i', $options->seller );
        
        // sometimes, rarely we are looking for an auction purchased by a specific buyer.
        // obviously these auctions are already closed. should i sanity check the closed param?
        if( isset( $options->buyer ) ) $clauses[] = $this->db->prep('buyer = %i', $options->buyer );
        
        // we can narrow by the person who is currently the leading bidder. can't search by past
        // bidders since that is more of a bid history search.
        // haven't written that yet.
        if( isset( $options->bidder ) ) $clauses[] = $this->db->prep('bidder = %i', $options->bidder );
        
        // are we looking for a bid-only auction?
        if( $options->only == 'bid' ) $clauses[] = 'price = 0';
        
        // how about a buy-now only auction?
        if( $options->only == 'buy' ) $clauses[] = 'step = 0';
        
        // look for items only above a given price range.
        if( $options->floor && ctype_digit( $options->floor ) ) $clauses[] = $this->db->prep('pricesort >= %i', $options->floor );
        
        // look for items only below a given price range.
        if( $options->ceiling && ctype_digit( $options->ceiling )) $clauses[] = $this->db->prep('pricesort <= %i', $options->ceiling );
        
        // how do we want the result set sorted?
        $sort = $options->sort;
        
        $order = '';
        switch( $sort ){
            case 'low_price':
                $order = 'pricesort ASC';
                break;
            case 'high_price':
                $order = 'pricesort DESC';
                break;

            case 'just_added':
                $order = 'created DESC';
                break;
                
        
            case 'expires_soon':
                $clauses[] = $this->db->prep('expires > %i', Souk\Util::now());
                $order = 'expires ASC';
                break;
                
            case 'expires_soon_delay':
                $clauses[] = $this->db->prep('expires > %i', Souk\Util::now() + Souk\UTIL::MIN_EXPIRE );
                $order = 'expires ASC';
                break;
                
            default:
                $key = $row['expires'] . '.' . $id;
                break;
        }
        
        $ds = Souk\Util::dateshard();
        
        
        // start with the shard a few weeks out from now, which is where the new listings are.
        $ds->setTimestamp(Souk\Util::now() + Souk\UTIL::MAX_EXPIRE);
        
        // if the auction is still active, we don't have to search the really old shards.
        // stop at the shard for this week.
        if( ! $options->closed ) $ds->setCutoff(1);
        
        // start looping throw the shards and querying.
        $ids = array();
        
        
        $where = ( $clauses ) ? 'WHERE ' . implode(' AND ', $clauses) : '';
        
        if( $order ) $order = ' ORDER BY ' . $order;
        
        // don't return more rows than the hard search limit imposed by souk.
        // after more than about 1000 rows, more results become meaningless. who paginates through all of that?
        // need them to somehow narrow their search more.
       $limit = 'LIMIT ' . Souk\Util::SEARCH_LIMIT;
        
        $ds = Souk\Util::dateshard();
        
        
        foreach( $ds as $shard ){
        
            
            $table = $this->table( $shard );
            
            if( \Gaia\Souk\Storage::isAutoSchemaEnabled() ) $this->create( $table );
                    
            $sql = "SELECT row_id, pricesort, created, expires FROM $table $where $order $limit";
            //print "\n" . $sql;

            // run the query
            $rs = $this->execute($sql);
            //print_r( $rs );

            // pull out all the rows matched by the query.
            // we are making the key of the id list contain the 
            // value of what we sort by, so we can do a keysort later, and order the
            // result in php since we have to span many shards.
            while( $row = $rs->fetch()){
                $id = Souk\Util::composeId( $shard, $row['row_id'] );
                switch( $sort ){
                    case 'low_price':
                    case 'high_price':
                        $key = $row['pricesort'] . '.' . $id;
                        break;

                    case 'just_added':
                        $key = $row['created'] . '.' . $id;
                        break;
                        
                
                    case 'expires_soon':
                    case 'expires_soon_delay':
                    default:

                        $key = $row['expires'] . '.' . $id;
                        break;
                }
                $ids[ $key ] = $id;
                
            }
            $rs->free();
        }
        
        // now that we are all done fetching the rows, sort.
        switch( $sort ){
            case 'low_price':
            case 'expires_soon':
            case 'expires_soon_delay':
                ksort( $ids, SORT_NUMERIC );
                break;
                
            default:
                krsort( $ids, SORT_NUMERIC );
                break;
        }
        
        // since we queried many shards we could potentially have quite a few more
        // ids than the max limit. slice off only the top most rows.
        // those are the ones we care about.
        // we only need the other values so we can sort in php across all the shards.
        // a little bit inefficient, but it is just a list of numbers, and we are gonna
        // cache it for a long time in the caching layer.
        return array_values( array_slice( $ids, 0, Souk\Util::SEARCH_LIMIT) );
        
    }
    
    public function close( $listing ){
        // grab the shard and row id.
        list( $shard, $row_id ) = Souk\Util::parseId( $listing->id );
        
        // update the listing.
        $table = $this->table( $shard );
        
                
        if( \Gaia\Souk\Storage::isAutoSchemaEnabled() ) $this->create( $table );
        
        if( ! Transaction::atStart() ) Transaction::add( $this->db );
            
        $sql = "UPDATE $table SET buyer = %i, touch = %i, closed = 1, pricesort = NULL WHERE row_id = %i";
        $rs = $this->execute($sql,
                    $listing->buyer,
                    $listing->touch,
                    $row_id);
        
        // should have affected a row. if it didn't toss an exception.
        if( $rs->affected() < 1 ) {
            throw new Exception('failed', $this->db );
        }
    }
    
    protected function table($shard){
        return $this->app . '_souk_' . $shard;
    }
    
    protected function execute( $query /*, .... */ ){
        $args = func_get_args();
        array_shift( $args );
        $rs = $this->db->execute( $qs = $this->db->prep_args( $query, $args ) );
        if( ! $rs ) throw new Exception('database error', array('db'=> $this->db, 'query'=>$qs, 'error'=>$this->db->error()) );
        return $rs;
    }
}