<?php
namespace Gaia\ShortCircuit;

/**
* A utility class designed to convert names into file paths for actions and views.
*/
class Resolver implements Iface\Resolver
{

    protected $appdir = '';
    const param_match = '#\\\\\(([a-z0-9_\-]+)\\\\\)#i';
    protected $urls = array();

    public function __construct( $dir = '', array $urls = null  ){
        $this->appdir = $dir;
        if( $urls ) $this->setUrls( $urls );
    }
    
    /**
    * convert a URI string into an action.
    */
    public function match( $uri, & $args ){
        $args = array();
        if( $this->urls ){
            $buildRegex = function ( $pattern ){
                $params = array();
                $regex  = preg_replace_callback(Resolver::param_match, function($match) use ( &$params ) {
                    $params[] = $match[1];
                    return '([a-z0-9\.+\,\;\'\\\&%\$\#\=~_\-]+)';
                
                }, preg_quote($pattern, '#'));
                return array('#^' . $regex . '$#i', $params );
            };
            
            foreach( $this->urls as $pattern => $action ){
                list( $regex, $params ) = $buildRegex( $pattern );
                if( ! preg_match( $regex, $uri, $matches ) ) continue;
                $args = array_slice($matches, 1);
                foreach( $params as $i => $key ){
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
    
    public function link( $name, array $params = array() ){
        $s = new \Gaia\Serialize\QueryString;
        $args = array();
        if( $this->urls ){
            $createLink = function( $pattern, array & $params ) use( $s ) {
                $url = preg_replace_callback(Resolver::param_match, function($match) use ( & $params, $s ) {
                    if( ! array_key_exists( $match[1], $params ) ) return '';
                    $ret = $s->serialize($params[ $match[1] ]);   
                    unset( $params[ $match[1] ] );
                    return $ret;
                }, preg_quote($pattern, '#'));
                return $url;
            };
                
            $match = FALSE;
            foreach( $this->urls as $pattern => $a ){
                if(  $a == $name ){
                    $match = TRUE;
                    break;
                }
            }
            if( $match ) {
                $url = $createLink( $pattern, $params );
                $qs = $s->serialize($params);
                if( $qs ) $qs = '?' . $qs;
                return $url . $qs;
            }
        }
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
    
    public function addURL( $pattern, $action ){
        $this->urls[ '/' . trim($pattern, '/') ] = $action;
    }
    
    public function setURLS( array $urls ){
        $this->urls = array();
        foreach( $urls as $pattern => $action ) {
            $this->addURL( $pattern, $action );
        }
    }
    
    public function urls(){
        return $this->urls;
    }
}

// EOF