<?php
namespace Gaia\ShortCircuit;

/**
* A utility class designed to convert names into file paths for actions and views.
*/
class Resolver implements Iface\Resolver
{

    protected $appdir = '';
    
    public function __construct( $dir = '' ){
        $this->appdir = $dir;
    }
    
    /**
    * convert a URI string into an action.
    */
    public function match( $uri, & $args ){
        $args = array();
        $uri = strtolower(trim( $uri, '/'));
        if( strlen( $uri ) < 1) $uri = 'index';
        $elements = explode('/', $uri );
        $ct = count( $elements );
        $pos = 1;
        while( $ct >= $pos ){
             $uri = implode('/', array_slice($elements, 0, $pos ));
            if( ! file_exists( $this->appdir . $uri ) ) break;
            $pos++;
        }

        while( strlen( ( $uri = implode('/', $elements ) ) ) > 0 ){
            $res = $this->get( $uri, 'action', TRUE);
            if( $res ) return $uri;
            $ele = array_pop($elements );
            if( $ele === NULL ) return '';
            array_unshift($args, $ele);
        }
        return '';
    }
    
    public function link( $name, array $params = array() ){
        $s = new \Gaia\Serialize\QueryString;
        //if( ! $this->match( $name, $args ) ) return '';
        
        $args = array();
        $p = array();
        foreach( $params as $k => $v ){
            if( is_int( $k ) ){ 
                $args[ $k ] = $s->serialize($v);
            } else {
                $p[ $k ] = $v;
            }
        }
        $params = $s->serialize($p);
        if( $params ) $params = '?' . $params;
        return '/' . $name . '/' . implode('/', $args ) . $params;
    }
    
    /**
    * convert a name into a file path.
    */
    public function get($name, $type, $skip_lower = FALSE ) {
        if( ! $skip_lower ) $name = strtolower($name);
        if( strlen( $name ) < 1 ) $name = 'index';
        $path = $this->appdir . $name . '.' . $type . '.php';
        if( ! file_exists( $path ) ) return '';
        return $path;
    }
    
    public function appdir(){
        return $this->appdir;
    }
    
    public function setAppDir( $dir ){
        return $this->appdir = $dir;
    }
}

// EOF