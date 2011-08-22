<?
class Local Implements Iface {

    function get( $request, $options = NULL ){
        return apc_fetch( $request );
    }
    
    function set($k, $v, $expires = 0 ){
        return apc_store( $k, $v, $expires );
    }
    
    function add( $k, $v, $expires = 0 ){
        return apc_add( $k, $v, $expires );
    }
    
    function replace( $k, $v, $expires ){
        if( ! $this->get( $k ) ) return FALSE;
        return $this->set( $k, $v, $expires );
    }
    
    function increment( $k, $step = 1){
        return apc_inc($k, $step );
    }
    
    function decrement( $k, $step = 1){
        return apc_dec( $k, $step );
    }
    
    function delete( $k ){
        return apc_delete( $k );
    }
}