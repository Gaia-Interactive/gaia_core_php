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
        $path = Resolver::get( $name, 'action' );
        if( ! $path ){
            if( $strict ) trigger_error('invalid action: ' . $name, E_USER_WARNING );
            return;
        }
        return include( $path );
    }
    
    public function resolveRoute( $name ){
         $name = Resolver::search( $name, 'action');
         return ( $name ) ? $name : '404';
    }
    
    function request(){
        return Router::request();
    }
    
    function config(){
        return Router::config();
    }
}
