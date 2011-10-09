<?php
namespace Gaia\ShortCircuit;
use Gaia\Container;

/**
 * Controller
 * this can be subclassed if you wish to change the default behavior of the controller.
 * To attach your new version, do one of the following: 
 *
 *    Gaia\ShortCircuit\Router::config()->controller = 'MyController';
 *    Gaia\ShortCircuit\Router::config()->controller = new MyController;
 *
 * The class will be used when Router::controller() is called.
 */
class Controller extends Container
{
   /**
    * call an action file.
    * this shouldn't produce any output.
    * by convention, it should return some sort of data, usually an array
    * that can be consumed by the view
    * this is mapped into the view container.
    * if strict, trigger errors if the path isn't found.
    */
    public function execute($name, $strict = TRUE ){
        $path = Resolver::get( $name, 'action' );
        if( ! $path ){
            if( $strict ) trigger_error('invalid action: ' . $name, E_USER_WARNING );
            return;
        }
        return include( $path );
    }
    
   /**
    * converts a URI into an action name.
    */
    public function resolveRoute( $name ){
         $name = Resolver::search( $name, 'action');
         return ( $name ) ? $name : '404';
    }
    
    /**
    * alias method for the router request object.
    */
    function request(){
        return Router::request();
    }
    
   /**
    * alias method for the router config object.
    */
    function config(){
        return Router::config();
    }
}
