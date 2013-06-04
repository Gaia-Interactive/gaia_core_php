<?php
namespace Gaia\Store;

/**
* Connect instrumentation to the caching calls. Optional add-on so we can know how many memcache calls
* are being made per page.
*/
class Stat extends Wrap  {
    protected $stat;

    public function __construct( Iface $core, Iface $stat ){
        parent::__construct( $core );
        $this->stat = $stat;
    }

    public function get( $k ){
        $multi = FALSE;
        if( is_scalar( $k ) ){
            $this->stat->increment('getkey_count');
        } elseif( is_array( $k ) ){
                $multi = count( $k );
                if( $multi < 1 ) return array();
                $this->stat->increment('getkey_count', $multi );
        } else {
            return FALSE;
        }
        $this->stat->increment('get_count');
        $_ts = microtime(TRUE);
        $res = $this->core->get( $k );
        $this->stat->increment('get_time', microtime(TRUE) - $_ts );

        if( $multi ){
            if( is_array( $res ) ){
                $diff = $multi - count( $res );
                if( $diff ) $this->stat->increment('miss_count', $diff );
                return $res;
            } else {
                $this->stat->increment('miss_count', $multi );
                return array();
            }
        } else {
            if( ! $res ) $this->stat->increment('miss_count');
            return $res;
        }
    }
    
    public function add( $k, $v, $ttl = 0 ){
        $_ts = microtime(TRUE);
        $this->stat->increment('add_count');
        $res = $this->core->add($k, $v, $ttl );
        $this->stat->increment('add_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function set( $k, $v, $ttl = 0 ){
        $_ts = microtime(TRUE);
        $this->stat->increment('set_count');
        $res = $this->core->set($k, $v, $ttl );
        $this->stat->increment('set_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function replace( $k, $v, $ttl = 0 ){
        $_ts = microtime(TRUE);
        $this->stat->increment('replace_count');
        $res = $this->core->replace($k, $v, $ttl );
        $this->stat->increment('replace_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function increment( $k, $v = 1 ){
        $_ts = microtime(TRUE);
        $this->stat->increment('increment_count');
        $res = $this->core->increment($k, $v );
        $this->stat->increment('increment_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function decrement( $k, $v = 1 ){
        $_ts = microtime(TRUE);
        $this->stat->increment('increment_count');
        $res = $this->core->decrement($k, $v );
        $this->stat->increment('increment_time', microtime(TRUE) - $_ts );
        return $res;
    }
    
    public function delete( $k ){
        $_ts = microtime(TRUE);
        $this->stat->increment('delete_count');
        $res = $this->core->delete( $k, 0);
        $this->stat->increment('delete_time', microtime(TRUE) - $_ts );
        return $res;
    }
}
