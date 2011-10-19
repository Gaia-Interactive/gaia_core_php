<?php
namespace Gaia\ShortCircuit;

/**
* A utility class designed to convert names into file paths for actions and views.
*/
class Resolver implements Iface\Resolver
{

    protected $appdir = '';
    protected $patterns = array();

     public function __construct( $dir = '', array $patterns = null ){
        if( $dir ) $this->setAppDir( $dir );
        if( $patterns ) $this->setPatterns( $patterns );
    }
    
    /**
    * convert a URI string into an action.
    */
    public function match( $uri, & $args ){
        if( ! is_array( $args ) ) $args = array();
        foreach( $this->patterns as $action => $pattern ){
            if( preg_match( $pattern['regex'], $uri, $matches ) ) {
                $args = array_slice($matches, 1);
                foreach( $pattern['params'] as $i => $key ){
                    if( ! isset( $args[ $i ] ) ) break;
                    $args[ $key ] = $args[ $i ];
                }
                return $action;
            }
        }
        
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
    
    public function link( $action, array $params = array() ){
    
        if( isset( $this->patterns[ $action ] ) ) {
            $pattern = $this->patterns[ $action ];
            $url_regex = $pattern['regex'];
            $url = str_replace(array('\\.', '\\-'), array('.', '-'), $url_regex);
            
            $args = array();
            
            foreach( $params as $k => $v ){
                if( is_int( $k ) ) {
                    $args[$k] = urlencode( $v );
                    unset( $params[ $k ] );
                }
            }
            
            foreach( $pattern['params'] as $i => $key ){
                if( isset( $params[ $key ] ) ) {
                    $args[ $i ] = urlencode($params[ $key ]);
                    unset( $params[ $key ] );
                }
            }
            $params = http_build_query($params);
            if( $params ) $params = '?' . $params;
            
            $args_count = count( $args );
            if ($args_count) {
                $groups = array_fill(0, $args_count, '#\(([^)]+)\)#'); 
                $url = preg_replace($groups, $args, $url, 1);
            }
            if( ! preg_match('/^#\^?([^#\$]+)/', $url, $matches) ) return $params;
            return $matches[1] . $params;
        }
    
        if( ! $this->match( $action, $args ) ) return '';
        
        $args = array();
        $p = array();
        foreach( $params as $k => $v ){
            if( is_int( $k ) ){ 
                $args[ $k ] = urlencode($v);
            } else {
                $p[ $k ] = $v;
            }
        }
        $params = http_build_query($p);
        if( $params ) $params = '?' . $params;
        return '/' . $action . '/' . implode('/', $args ) . $params;
    }
    
    /**
    * convert a name into a file path.
    */
    public function get($name, $type, $skip_lower = FALSE ) {
        if( ! $skip_lower ) $name = strtolower($name);
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
    
    public function addPattern( $action, $pattern ){
        if( is_array( $pattern ) ){
            if( ! isset( $pattern['regex'] ) ) return;
            if( ! is_array( $pattern['params'] ) ) $pattern['params'] = array();
            return $this->patterns[ $action ] = $pattern;
        } elseif( is_scalar( $pattern ) ) {
            return $this->patterns[ $action ] = array('regex'=>$pattern, 'params'=>array() );
        }
    }
    
    public function setPatterns( array $patterns ){
        $this->patterns = array();
        foreach( $patterns as $action => $pattern ) {
            $this->addPattern($action, $pattern );
        }
    }
    
    public function patterns(){
        return $this->patterns;
    }

}

// EOF