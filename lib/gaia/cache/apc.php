<?
class APC {

    protected $namespace;
    
    function __construct( $namespace = NULL ){
        $this->namespace = $namespace;
    }
    
    function fetch( $request, & $success = FALSE ){
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
        
        // ask for the keys
        $result = apc_fetch( $list, $success );
        
        // did we find it?
        // if it didn't return an array it blew up with an internal error.
        // this should never happen, but anyway, here it is.
        if( ! is_array( $result ) ) return $result;
        
        // calculate the length of the namespace for later
        $len = strlen( $this->namespace);
        
        // convert the result from the cache back into key/value pairs without a prefix.
        // overwrite the empty values we populated earlier.
        foreach( $result as $k=>$v) $matches[substr($k, $len)] = $v;
        
        // find the missing ones.
        $missing = array_keys( $matches, NULL);
        
        // get rid of any of the missing keys now
        foreach( $missing as $k ) unset( $matches[ $k] );
        
        // if the request wasn't a multi, return the data.
        if( $single ) return isset( $matches[ $request ] ) ? $matches[ $request ] : FALSE;
        
        return $matches;
    }
    
    function store($k, $v, $ttl = 0 ){
        return apc_store( $this->namespace . $k, $v, $ttl );
    }
    
    function inc( $k, $step = 1, & $success = FALSE ){
        return apc_inc( $this->namespace . $k, $step, $success );
    }
    
    function dec( $k, $step = 1, & $success = FALSE ){
        return apc_dec( $this->namespace . $k, $step, $success );
    }
    
    function cas( $k, $old, $new ){
        return apc_cas( $this->namespace . $k, $old, $new );
    }
    
    function delete( $k ){
        return apc_delete( $this->namespace . $k );
    }
    
    function exists( $request ){
     // we want to work with a list of keys
        $keys =  ( $single = is_scalar( $request ) ) ? array( $request ) : $request;
        
        // if we couldn't convert the value to an array, skip out
        if( ! is_array($keys ) ) return FALSE;
        
        // initialize the array for keeping track of all the results.
        $list = array();
        
        // write all the keynames with the namespace prefix as null values into our result set
        foreach( $keys as $k ){
            $list[] = $this->namespace . $k;
        }
        
        // ask for the keys
        $result = apc_fetch( $list );
        
        // did we find it?
        // if it didn't return an array it blew up with an internal error.
        // this should never happen, but anyway, here it is.
        if( ! is_array( $result ) ) return $result;
        
        // calculate the length of the namespace for later
        $len = strlen( $this->namespace);
        
        $res = array();
        foreach( $result as $i=>$k) $res[$i] = substr($k, $len);
        
        // if the request wasn't a multi, return the data.
        if( $single ) return in_array( $res, $request ) ? TRUE : FALSE;
        
        return $res;
    }
}