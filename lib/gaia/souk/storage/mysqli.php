<?php
namespace Gaia\Souk\Storage;
use \Gaia\DB\Driver;
use \Gaia\Exception;
use \Gaia\Store;
use \Gaia\Souk;
use \Gaia\DB\Transaction;

class MySQLi implements IFace {

    protected $db;
    protected $app;
    protected $user_id;
    protected $dsn;
    public function __construct( \Gaia\DB\Iface $db, $app, $user_id, $dsn){
        if( ! $db->isa('mysqli') ) throw new Exception('invalid driver', $db );
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
        
        $rs = $this->execute('SHOW TABLES LIKE %s', $table);
        $row = $rs->fetch_row();
        $rs->free_result();
        if( ! $row ) {
             $this->execute(
            "CREATE TABLE IF NOT EXISTS $table (
              `row_id` int unsigned NOT NULL auto_increment,
              `seller` bigint unsigned NOT NULL,
              `buyer` bigint unsigned NOT NULL DEFAULT '0',
              `item_id` int unsigned NOT NULL DEFAULT '0',
              `quantity` bigint unsigned NOT NULL DEFAULT '0',
              `price` bigint unsigned default '0',
              `pricesort` bigint unsigned default '0',
              `step` bigint unsigned default '0',
              `bid` bigint unsigned default '0',
              `proxybid` bigint unsigned default '0',
              `bidcount` int unsigned default '0',
              `bidder` bigint unsigned default '0',
              `reserve` bigint unsigned default NULL,
              `closed` tinyint unsigned NOT NULL DEFAULT '0',
              `created` int unsigned,
              `expires` int unsigned,
              `touch` int(10) unsigned,
              PRIMARY KEY  (`row_id`),
              KEY `closed` (`closed`),
              KEY `created` (`created`),
              KEY `expires` (`expires`),
              KEY `pricesort` (`pricesort`),
              KEY `item` (`item_id`),
              KEY `price` (`price`),
              KEY `step` (`step`),
              KEY `seller` (`seller`),
              KEY `bidder` (`bidder`),
              KEY `buyer` (`buyer`)
            ) ENGINE=InnoDB"
                    
            );
        }
        
        $table_attr = $table .'_attr';
        
        $rs = $this->execute('SHOW TABLES LIKE %s', $table_attr);
        $row = $rs->fetch_row();
        $rs->free_result();
        if( ! $row ) {
             $this->execute(
            "CREATE TABLE IF NOT EXISTS $table_attr (
              `row_id` int unsigned NOT NULL,
              `attributes` varchar(5000) character set utf8,
              PRIMARY KEY  (`row_id`)
            ) ENGINE=InnoDB"
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
            if( $this->db->affected_rows < 1 ) throw new Exception('failed', $this->db );
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
        $row_id = $this->db->insert_id;
        $listing->id = Souk\Util::composeId( $shard,  $row_id);
        
        if( $attributes ){
            $table_attr = $table . '_attr';
            $sql = "INSERT INTO `$table_attr` (`row_id`, `attributes`) 
                    VALUES (%i, %s) 
                    ON DUPLICATE KEY UPDATE `attributes` = VALUES(`attributes`)";
            $this->execute( $sql, $row_id, json_encode( $attributes ) );
        }
    }
    
    public function pending( $ts = 0 ){
        $ts = Souk\Util::now() - $age;
        $stack = array();
        
        $list = array();
        foreach( Souk\Util::dateshard() as $shard ){
            $table = $this->table( $shard );
            if( \Gaia\Souk\Storage::isAutoSchemaEnabled() ) $this->create( $table );
            $rs = $this->execute("SELECT row_id FROM $table WHERE closed = 0 AND expires < ? ORDER BY expires ASC", $ts );
            while( $row = $rs->fetch_assoc() ) $list[] = Souk\Util::composeId( $shard, $row['row_id'] );
            
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
            if( $this->db->affected_rows < 1 ) {
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
            if( $lock ) $sql .= ' FOR UPDATE';
            $rs = $this->execute($sql, $row_ids);
            
            // grab the rows returned and populate the result as Souk\listings.
            $row_ids = array();
            while(  $row = $rs->fetch_assoc() ){
                $row_id = $row_ids[] = $row['row_id'];
                unset( $row['row_id'] );
                $listing = Souk\Util::listing( $row );
                $listing->id = Souk\Util::composeId( $shard, $row_id );
                $result[ $listing->id ] = $listing;
            }
            $rs->free_result();
            
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
            if( $this->db->affected_rows < 1 ) continue;
            
            // someone stored attributes! merge them in.
            // stored as json in the db. deserialize and layer on top of the listing object.
            while( $row = $rs->fetch_assoc() ){
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
            $rs->free_result();
            
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
        $clauses[] = $this->db->format_query('closed = ?', $options->closed ? 1 : 0);
        
        // if we have a specific item id we are looking for, query for that.
        if( isset( $options->item_id ) ) $clauses[] = $this->db->format_query('item_id = %i', $options->item_id );
        
        // do we know the seller?
        if( isset( $options->seller ) ) $clauses[] = $this->db->format_query('seller = %i', $options->seller );
        
        // sometimes, rarely we are looking for an auction purchased by a specific buyer.
        // obviously these auctions are already closed. should i sanity check the closed param?
        if( isset( $options->buyer ) ) $clauses[] = $this->db->format_query('buyer = %i', $options->buyer );
        
        // we can narrow by the person who is currently the leading bidder. can't search by past
        // bidders since that is more of a bid history search.
        // haven't written that yet.
        if( isset( $options->bidder ) ) $clauses[] = $this->db->format_query('bidder = %i', $options->bidder );
        
        // are we looking for a bid-only auction?
        if( $options->only == 'bid' ) $clauses[] = 'price = 0';
        
        // how about a buy-now only auction?
        if( $options->only == 'buy' ) $clauses[] = 'step = 0';
        
        // look for items only above a given price range.
        if( $options->floor && ctype_digit( $options->floor ) ) $clauses[] = $this->db->format_query('pricesort >= %i', $options->floor );
        
        // look for items only below a given price range.
        if( $options->ceiling && ctype_digit( $options->ceiling )) $clauses[] = $this->db->format_query('pricesort <= %i', $options->ceiling );
        
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
                $clauses[] = $this->db->format_query('expires > %i', Souk\Util::now());
                $order = 'expires ASC';
                break;
                
            case 'expires_soon_delay':
                $clauses[] = $this->db->format_query('expires > %i', Souk\Util::now() + Souk\UTIL::MIN_EXPIRE );
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
            while( $row = $rs->fetch_assoc()){
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
        if( $this->db->affected_rows < 1 ) {
            throw new Exception('failed', $this->db );
        }
    }
    
    protected function table($shard){
        return $this->app . '_souk_' . $shard;
    }
    
    protected function execute( $query /*, .... */ ){
        $args = func_get_args();
        array_shift( $args );
        $rs = $this->db->query( $qs = $this->db->format_query_args( $query, $args ) );
        if( ! $rs ) throw new Exception('database error', array('db'=> $this->db, 'query'=>$qs ) );
        return $rs;
    }
}