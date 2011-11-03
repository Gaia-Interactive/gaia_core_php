<?php
namespace Gaia\ShortCircuit;

class PatternResolver implements Iface\Resolver {
    
    protected $core;
    protected $patterns = array();
    
     public function __construct( Iface\Resolver $resolver, array $patterns = null ){
        $this->core = $resolver;
        if( $patterns ) $this->setPatterns( $patterns );
    }

    public function match( $uri, & $args ){
        $args = array();
        foreach( $this->patterns as $action => $pattern ){
            if( isset( $pattern['action'] ) ) $action = $pattern['action'];
            if( preg_match( $pattern['regex'], $uri, $matches ) ) {
                $args = array_slice($matches, 1);
                foreach( $pattern['params'] as $i => $key ){
                    if( ! isset( $args[ $i ] ) ) break;
                    $args[ $key ] = $args[ $i ];
                }
                return $action;
            }
        }
        return $this->core->match( $uri, $args);
    }
    
    public function addPattern( $pattern, $action = NULL ){
        if( is_array( $pattern ) ){
            if( ! isset( $pattern['regex'] ) ) return;
            if( ! is_array( $pattern['params'] ) ) $pattern['params'] = array();
            if( $action === NULL || is_int( $action ) ){
                if( ! isset( $pattern['action'] ) ) return;
                return $this->patterns[] = $pattern;
            }
            return $this->patterns[ $action ] = $pattern;
        } elseif( is_scalar( $pattern ) ) {
            if(  $action === NULL || is_int( $action ) ) return;
            return $this->patterns[ $action ] = array('regex'=>$pattern, 'params'=>array() );
        }
    }
    
    public function setPatterns( array $patterns ){
        $this->patterns = array();
        foreach( $patterns as $action => $pattern ) {
            $this->addPattern( $pattern, $action );
        }
    }
    
    public function patterns(){
        return $this->patterns;
    }
    
    public function link( $action, array $params = array() ){
        $s = new \Gaia\Serialize\QueryString;
        if( ! isset( $this->patterns[ $action ] ) ){
            $match = FALSE;
            foreach( $this->patterns as $pattern ){
                if( isset( $pattern['action'] ) && $pattern['action'] == $action ){
                    $match = TRUE;
                    break;
                }
            }
            if( ! $match ) return $this->core->link( $action, $params );
        } else {
            $pattern = $this->patterns[ $action ];
        }
        $url_regex = $pattern['regex'];
        $url = str_replace(array('\\.', '\\-'), array('.', '-'), $url_regex);
        
        $args = array();
        
        foreach( $params as $k => $v ){
            if( is_int( $k ) ) {
                $args[$k] = $s->serialize( $v );
                unset( $params[ $k ] );
            }
        }
        
        foreach( $pattern['params'] as $i => $key ){
            if( array_key_exists( $key, $params ) ) {
                $args[ $i ] = $s->serialize($params[ $key ]);
                unset( $params[ $key ] );
            }
        }
        
        
        $args_count = count( $args );
        if ($args_count) {
            $groups = array_fill(0, $args_count, '#\(([^)]+)\)#'); 
            $url = preg_replace($groups, $args, $url, 1);
        }
        if( ! preg_match('/^#\^?([^#\$]+)/', $url, $matches) ) return $this->core->link($action, $params );
        $qs = $s->serialize($params);
        if( $qs ) $qs = '?' . $qs;
        return '/' . trim($matches[1], '/') . $qs;
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
