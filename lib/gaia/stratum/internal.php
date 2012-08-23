<?php
namespace Gaia\Stratum;
use Gaia\Exception;

class Internal implements Iface {

    protected $kvp = array();
    
    function __construct( array $kvp = array() ){
        foreach( $kvp as $k => $v ){
            $this->kvp[ $k ] = intval( $v );
        }
    }
    
    public function store( $constraint, $stratum ){
        $this->kvp[ $constraint ] = $stratum;
    }
    public function delete( $constraint ){  
        if( ! isset( $this->kvp[ $constraint ] ) ) return FALSE;
        unset( $this->kvp[ $constraint ] );
        return TRUE;
    }
    
    public function query( array $params = array() ){
        $search = NULL;
        $min = NULL;
        $max = NULL;
        $sort = 'ASC';
        $limit = NULL;
        $result = $this->kvp;
        asort( $result );
        
        if( isset( $params['search'] ) ) $search = $params['search'];
        if( isset( $params['min'] ) ) $min = $params['min'];
        if( isset( $params['max'] ) ) $max = $params['max'];
        if( isset( $params['sort'] ) ) $sort = $params['sort'];
        if( isset( $params['limit'] ) ) $limit = $params['limit'];
        $sort = strtoupper( $sort );
        if( $sort == 'DESC' ) $result = array_reverse( $result );
        
        if( $min !== NULL ) {
            foreach( $result as $k => $v ){
                if( $v < $min ) unset( $result[ $k ] );
            }
        }
        
        if( $max !== NULL ) {
            foreach( $result as $k => $v ){
                if( $v > $max ) unset( $result[ $k ] );
            }
        }
        
        if( $search !== NULL ){
            if( ! is_array( $search ) ) $search = array( $search );
            foreach( $search as $stratum ){
                if( ! ctype_digit( strval( $stratum ) ) ) {
                    throw new Exception('invalid search parameters', $search );
                }
            }
            $res = array();
            foreach( $result as $k => $v ){
                if( ! in_array( $v, $search ) ) continue;
                $res[ $k ] = $v;
            }
            $result = $res;
            
        }
        
        if( $limit !== NULL ) {
            $limit = str_replace(' ', '', $limit );
            $parts = explode(',', $limit, 2);
            if( count( $parts ) == 2 ){
                $offset = $parts[0];
                $limit = $parts[1];
            } else {
                $offset = '0';
                $limit = $parts[0];
            }
            if( ctype_digit( $limit ) && ctype_digit( $offset ) ){
                $result = array_slice( $result, $offset, $limit, $preserve = TRUE );
            }
        }
        
        return $result;
    }

}
