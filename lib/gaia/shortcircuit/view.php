<?php
namespace Gaia\ShortCircuit;
use Gaia\Container;

/**
 * Circuit View
 * @package CircuitMVC
 */
class View extends Container
{
   /**
    * Render a template
    */
    public function render($name, $strict = TRUE ){
        $path = self::resolve( $name, 'view' );
        if( ! $path ){
            if( $strict ) trigger_error('invalid view: ' . $name, E_USER_WARNING );
            return;
        }
        include( $path );
    }
    
    public static function resolve($name, $type ) {
        $name = strtolower($name);
        $type = strtolower($type);
        $dir =  Router::appdir();
        $apc_key = 'shortcircuit/' . $type . '/' . $dir . '/' . $name;
        $path = apc_fetch( $apc_key );
        if( $path ) return $path;
        $path = $dir . $name . '.' . $type . '.php';
        if( ! file_exists( $path ) ) return FALSE;
        apc_store( $apc_key, $path, 30);
        return $path;
    } 
    
    /**
    * Render a template and return it as a string
    */
    public function fetch( $name, $strict=TRUE ){
        ob_start();
        $this->render( $name, $strict );
        return ob_get_clean();
    }
}
