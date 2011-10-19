<?php
namespace Gaia\ShortCircuit;

class PatternResolver extends Resolver implements Iface\Resolver {
    
    protected $patterns = array();
    
     public function __construct( $dir = '', array $patterns = null ){
        parent::__construct( $dir );
        if( $patterns ) $this->setPatterns( $patterns );
    }

    public function match( $uri, & $args ){
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
        return '';
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
    
    public function link( $action, array $params = array() ){
        if( ! isset( $this->patterns[ $action ] ) ) return '';
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
}
