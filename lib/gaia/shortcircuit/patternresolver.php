<?php
namespace Gaia\ShortCircuit;

class PatternResolver implements Iface\Resolver {
    
    const param_match = '#\\\\\(([a-z0-9_\-]+)\\\\\)#i';

    protected $core;
    protected $patterns = array();
    
     public function __construct( Iface\Resolver $resolver, array $patterns = null ){
        $this->core = $resolver;
        if( $patterns ) $this->setPatterns( $patterns );
    }

    public function match( $uri, & $args ){
        $args = array();
        
        $buildRegex = function ( $pattern ){
            $params = array();
            $regex  = preg_replace_callback(PatternResolver::param_match, function($match) use ( &$params ) {
                $params[] = $match[1];
                return '([a-z0-9\.+\,\;\'\\\&%\$\#\=~_\-]+)';
            
            }, preg_quote($pattern, '#'));
            return array('#^' . $regex . '$#i', $params );
        };
        
        foreach( $this->patterns as $pattern => $action ){
            list( $regex, $params ) = $buildRegex( $pattern );
            if( ! preg_match( $regex, $uri, $matches ) ) continue;
            $args = array_slice($matches, 1);
            foreach( $params as $i => $key ){
                if( ! isset( $args[ $i ] ) ) break;
                $args[ $key ] = $args[ $i ];
            }
            return $action;
            
        }
        return $this->core->match( $uri, $args);
    }
    
    public function addPattern( $pattern, $action ){
        $this->patterns[ '/' . trim($pattern, '/') ] = $action;
    }
    
    public function setPatterns( array $patterns ){
        $this->patterns = array();
        foreach( $patterns as $pattern => $action ) {
            $this->addPattern( $pattern, $action );
        }
    }
    
    public function patterns(){
        return $this->patterns;
    }
    
    public function link( $action, array $params = array() ){
        $s = new \Gaia\Serialize\QueryString;
        
        $createLink = function( $pattern, array & $params ) use( $s ) {
            $url = preg_replace_callback(PatternResolver::param_match, function($match) use ( & $params, $s ) {
                if( ! array_key_exists( $match[1], $params ) ) return '';
                $ret = $s->serialize($params[ $match[1] ]);   
                unset( $params[ $match[1] ] );
                return $ret;
            }, preg_quote($pattern, '#'));
            return $url;
        };
            
        
        $match = FALSE;
        foreach( $this->patterns as $pattern => $a ){
            if(  $a == $action ){
                $match = TRUE;
                break;
            }
        }
        if( ! $match ) return $this->core->link( $action, $params );
        $url = $createLink( $pattern, $params );
        $qs = $s->serialize($params);
        if( $qs ) $qs = '?' . $qs;
        return $url . $qs;
    }
    
    public function appdir(){
        return $this->core->appdir();
    }
    
    public function setAppDir( $dir ){
        return $this->core->setAppDir( $dir );
    }
    
    public function get( $name, $type ){
        return $this->core->get( $name, $type );
    }
    
    public function __call( $method, $args ){
        return call_user_func_array( array($this->core, $method), $args );
    }
}
