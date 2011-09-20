<?php
namespace Gaia\Cache;
use Gaia\Container;

class Callback extends Wrap
{

    public function __construct( Iface $core, $options ){
        parent::__construct( $core );
        if( ! $options instanceof Container ) $options = new Container( $options );
        $this->options = $options;
    }

    public function get( $request ){
        if( is_array( $request ) ) return $this->getMulti( $request );
        if( ! is_scalar( $request ) ) return FALSE;
        $res = $this->getMulti( array( $request ) );
        if( ! isset( $res[ $request ] ) ) return FALSE;
        return $res[ $request ];
    }
    
    protected function getMulti( array $keys ){
        
        // initialize the array for keeping track of all the results.
        // write all the keynames as null values into our result set
        $matches = array_fill_keys($keys, NULL);
        
        // ask for the keys from mecache object
        $result = $this->core->get( $keys );
        
        // did we find it?
        // if memcache didn't return an array it blew up with an internal error.
        // this should never happen, but anyway, here it is.
        if( ! is_array( $result ) ) return $result;
        
        // overwrite the empty values we populated earlier.
        foreach( $result as $k=>$v) $matches[$k] = $v;
        
        // find the missing ones.
        $missing = array_keys( $matches, NULL, TRUE);
        
        // get rid of any of the missing keys now
        foreach( $missing as $k ) unset( $matches[ $k] );
        
        // here is where we call a callback function to get any additional rows missing.
        
        if( count($missing) > 0 && isset( $this->options->callback) && is_callable($this->options->callback) ){
            $result = call_user_func( $this->options->callback,$missing);
            if( ! is_array( $result ) ) return $matches;
            if( ! isset( $this->options->timeout ) ) $this->options->timeout = 0;
            if( ! isset( $this->options->method) ) $this->options->method = 'set';
            if( $this->options->cache_missing ){
                foreach( $missing as $k ){
                    if( ! isset( $result[ $k ] ) ) $result[$k] = self::UNDEF;
                }
            }
                        
            foreach( $result as $k=>$v ) {
                $matches[ $k ] = $v;
                $this->core->{$this->options->method}($k, $v, $this->options->timeout);
            }
        }
        
        foreach( $matches as $k => $v ){
            if( $v === self::UNDEF ) unset( $matches[ $k ] );
        }
        if( isset( $this->options->default ) ) {
            foreach( $missing as $k ){
                if( ! isset( $matches[ $k ] ) ) $matches[$k] = $this->options->default;
            }
        }
        return $matches;
    }
}
