<?php
namespace Gaia\ShortCircuit;
use Gaia\Container;

/**
 * Controller
 */
class Controller extends Container
{
   /**
    * Render a template
    */
    public function execute($name, $strict = TRUE ){
        $path = Router::resolve( $name, 'action' );
        if( ! $path ){
            if( $strict ) trigger_error('invalid action: ' . $name, E_USER_WARNING );
            return;
        }
        return include( $path );
    }
    
    function request(){
        return Router::request();
    }
    
    function config(){
        return Router::config();
    }
}
