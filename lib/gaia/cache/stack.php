<?php
/*
* Class that implements a list of memcache objects
*/
namespace Gaia\Cache;
use Memcache;

class Stack {

	private $core;
    const MAX_RANGE = 5000;

    public function __construct( Memcache $core ){
    	$this->core = $core;
    }
	
    public function add( $value, $expires = NULL ){
        if( ! ( $pos = $this->core->increment('i') ) ){
            if(! $this->core->add('i', 1) ) return FALSE;
            $pos = 1;
        }
        if( ! is_numeric( $expires ) || $expires < 1 ) $expires = NULL;
        $this->core->set($pos, $value, 0, $expires);
        return $pos;
    }
    
    public function count(){
        $m = $this->cacher();
        $data = $m->get( array('i', 'a') );
        if( ! is_array( $data ) ) return 0;
        if( ! isset( $data['i'] ) || $data['i'] < 1) return 0;
        if( ! isset( $data['a'] ) || $data['a'] < 0 ) $data['a'] = 0;
        $ct = $data['i'] - $data['a'];
        if( $ct < 1 ) return 0;
        return $ct;
    }

	public function shift( $depth = NULL ){
	        $data = $this->core->get( array('i', 'a') );
	        if( ! is_array( $data ) ) return FALSE;
	        if( ! isset( $data['i'] ) ) return FALSE;
	        if( ! isset( $data['a'] ) ) {
	            $data['a'] = 0;
	            $a_unset = TRUE;
	        }
	        if( $data['a'] < 0 ) $data['a'] = 0;
	        if( $depth !== NULL &&  $data['i'] - $data['a'] > $depth  ) $data['a'] = $depth;
	        if( $a_unset ) $this->core->add('a', $data['a']);
	        while( ( $data['a'] = $this->core->increment('a') ) ){
	            $res = $this->core->get($data['a']);
	            $this->core->delete( $data['a'] );
	            if( $res !== FALSE ) return $res;
	            if( $data['a'] >= $data['i'] ) {
	                $this->core->decrement('a');
	                return FALSE;
	            }
	        }
	        return FALSE;
	}

    public function get( $k ){
        return $this->core->get($k);
    }
    
    public function delete( $k ) {
    	return $this->core->delete($k);
    }
    
    public function end(){
        return $this->core->get('i');
    }
    
    public function start(){
        return $this->core->get('a');
    }
    
    public function recent( $limit = 10 ){
        if( ! is_numeric( $limit ) || $limit > self::MAX_RANGE || $limit < 1 ) $limit = self::MAX_RANGE;
        $high = $this->core->get('i');
        if( $high < 1 ) return array();
        $low = $high - $limit;
        if( $low < 1 ) $low = 1;
        return $this->get( range(  $high - ( $limit - 1), $high) );
    }
    
    public function reset() {
    	$this->core->delete('i');
    }
}

