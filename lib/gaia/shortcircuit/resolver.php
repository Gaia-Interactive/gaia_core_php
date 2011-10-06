<?php
namespace Gaia\ShortCircuit;

/**
* A utility class designed to convert names into file paths for actions and views.
*/
class Resolver {

    protected $appdir = '';
    
    public function __construct( $dir = '' ){
        $this->appdir = $dir;
    }
    
    /**
    * convert a URI string into an action.
    */
    public function search( $name, $type ){
        $args = explode('/', $name );
        do{
            $n = implode('/', $args );
            if( strlen($n) < 1 ) break;
            $res = $this->get( $n, $type);
            if( ! $res ) continue;
            return $n;
        } while( array_pop( $args ) );
        return '';
    }
    
    /**
    * convert a name into a file path.
    */
    public function get($name, $type ) {
        $name = strtolower($name);
        $type = strtolower($type);
        if( strlen( $name ) < 1 ) $name = 'index';
        $path = $this->appdir . $name . '.' . $type . '.php';
        if( ! file_exists( $path ) ) {
            $path = $this->appdir . $name . '/index.' . $type . '.php';
            if( ! file_exists( $path ) ) $path = '';
        }
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