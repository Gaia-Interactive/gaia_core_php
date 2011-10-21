<?php
namespace Gaia\Cache;

class Prefix extends Wrap
{
    private $prefix = '';
    
    public function __construct( $core, $prefix ){ 
        parent::__construct( $core );
        $this->prefix = $prefix;
    }
    
    public function decrement($key, $value = 1){ 
        return $this->core->decrement($this->prefix . $key, $value);
    }
    
    public function delete($key) {
        return $this->core->delete($this->prefix . $key); 
    }
    
    public function get( $request){
        if( is_array( $request ) ) return $this->getMulti( $request );
        if( ! is_scalar( $request ) ) return FALSE;
        $res = $this->getMulti( array( $request ) );
        if( ! isset( $res[ $request ] ) ) return FALSE;
        return $res[ $request ];
    }
        
    protected function getMulti( array $keys ){
        
        // initialize the array for keeping track of all the results.
        $list = array();
        
        // write all the keynames with the prefix prefix as null values into our result set
        foreach( $keys as $k ){
            $list[] = $this->prefix . $k;
        }
        
        // ask for the keys from mecache object ...
        $result = $this->core->get( $list );
        
        // did we find it?
        // if memcache didn't return an array it blew up with an internal error.
        // this should never happen, but anyway, here it is.
        if( ! is_array( $result ) ) return array();
        
        // calculate the length of the prefix for later so we can 
        $len = strlen( $this->prefix );
        
        // convert the result from the cache back into key/value pairs without a prefix.
        $list = array();
        foreach( $result as $k=>$v) $list[substr($k, $len)] = $v;
        
        return $list;
    }
    
    public function increment($key, $value = 1){
        return $this->core->increment($this->prefix . $key, $value); 
    }
    
    public function replace($key, $value, $expire = NULL){ 
        return $this->core->replace($this->prefix . $key, $value, $expire); 
    }
    
    public function set($key, $value, $expire = NULL){ 
        return $this->core->set($this->prefix . $key, $value, $expire); 
    }
    
    public function add($key, $value, $expire = NULL){
        return $this->core->add($this->prefix . $key, $value, $expire);
    }
    
    public function __call($method, array $args){
        return call_user_func_array( array( $this->core, $method), $args ); 
    }
}
