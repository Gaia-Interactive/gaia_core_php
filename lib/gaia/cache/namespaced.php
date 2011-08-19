<?php
namespace Gaia\Cache;
use Memcache;

class Namespaced extends Base
{
    private $namespace = '';
    private $core;
    const UNDEF = "\0__undef__\0";
    
    function __construct( Memcache $core, $namespace ){ 
        $this->core = $core; 
        $this->namespace = $namespace;
    }
    
    function decrement($key, $value = 1){ 
        return $this->core->decrement($this->namespace . $key, $value);
    }
    
    function flush(){ 
        return FALSE; 
    }
    
    function delete($key) {
        return $this->core->delete($this->namespace . $key); 
    }
    
    function get($request, $options = NULL){
        // we want to work with a list of keys
        $keys =  ( $single = is_scalar( $request ) ) ? array( $request ) : $request;
        
        // if we couldn't convert the value to an array, skip out
        if( ! is_array($keys ) ) return FALSE;
        
        // initialize the array for keeping track of all the results.
        $list = $matches = array();
        
        // write all the keynames with the namespace prefix as null values into our result set
        foreach( $keys as $k ){
            $matches[ $k ] = NULL;
            $list[] = $this->namespace . $k;
        }
        
        // ask for the keys from mecache object ... should we pass along the options down internally?
        // think not, but just asking.
        $result = $this->core->get( $list );
        
        // did we find it?
        // if memcache didn't return an array it blew up with an internal error.
        // this should never happen, but anyway, here it is.
        if( ! is_array( $result ) ) return $result;
        
        // calculate the length of the namespace for later so we can 
        $len = strlen( $this->namespace);
        
        // convert the result from the cache back into key/value pairs without a prefix.
        // overwrite the empty values we populated earlier.
        foreach( $result as $k=>$v) $matches[substr($k, $len)] = $v;
        
        // find the missing ones.
        $missing = array_keys( $matches, NULL, TRUE);
        
        // get rid of any of the missing keys now
        foreach( $missing as $k ) unset( $matches[ $k] );
        
        // here is where we call a callback function to get any additional rows missing.
        
        if( count($missing) > 0 && is_array( $options ) && isset( $options['callback']) && is_callable($options['callback']) ){
            $result = call_user_func( $options['callback'],$missing);
            if( ! is_array( $result ) ) return $matches;
            if( ! isset( $options['compression']) ) $options['compression'] = 0;
            if( ! isset( $options['timeout'] ) ) $options['timeout'] = 0;
            if( ! isset( $options['method']) ) $options['method'] = 'set';
            if( isset( $options['cache_missing'] ) && $options['cache_missing'] ){
                foreach( $missing as $k ){
                    if( ! isset( $result[ $k ] ) ) $result[$k] = self::UNDEF;
                }
            }
                        
            foreach( $result as $k=>$v ) {
                $matches[ $k ] = $v;
                $this->core->{$options['method']}($this->namespace . $k, $v, $options['compression'], $options['timeout']);
            }
        }
        
        foreach( $matches as $k => $v ){
            if( $v === self::UNDEF ) unset( $matches[ $k ] );
        }
        if( isset( $options['default'] ) ) {
            foreach( $missing as $k ){
                if( ! isset( $matches[ $k ] ) ) $matches[$k] = $options['default'];
            }
        }
        if( $single ) return isset( $matches[ $request ] ) ? $matches[ $request ] : FALSE;
        
        return $matches;
    }
    
    function increment($key, $value = 1){
        return $this->core->increment($this->namespace . $key, $value); 
    }
    
    function replace($key, $value, $flag = NULL, $expire = NULL){ 
        return $this->core->replace($this->namespace . $key, $value, $flag, $expire); 
    }
    
    function set($key, $value, $flag = NULL, $expire = NULL){ 
        return $this->core->set($this->namespace . $key, $value, $flag, $expire); 
    }
    
    function add($key, $value, $flag = NULL, $expire = NULL){
        return $this->core->add($this->namespace . $key, $value, $flag, $expire);
    }
    
    function __call($method, $args){
        return call_user_func_array( array( $this->core, $method), $args ); 
    }
}
