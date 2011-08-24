<?php 
namespace Gaia\ShortCircuit;
use Gaia\Container;
use Gaia\Exception;


/**
 * Routes requests to the correct shortcircuit object, performs the action, and routes
 * the response to a view to render the output.
 */
class Router {

    const ABORT = '__ABORT__';
    
    protected static $request;
    protected static $config;
    
    public static function request(){
        if( isset( self::$request ) ) return self::$request;
        return self::$request = new Request( $_REQUEST );
    }
    
    public static function config(){
        if( isset( self::$config ) ) return self::$config;
        self::$config = new Container();
        self::$config->controller = 'Gaia\ShortCircuit\Controller';
        self::$config->view = 'Gaia\ShortCircuit\View';
        self::$config->uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $r = self::request();
        if (isset($r->{'_'})) {
            $action = $r->{'_'};
        }
        else if (isset($_SERVER['PATH_INFO'])) {
            $action = $_SERVER['PATH_INFO'];
        }
        else {
            $pos = strpos(self::$config->uri, '?');
            $action =( $pos === FALSE ) ? 
                self::$config->uri : substr(self::$config->uri , 0, $pos);
        }
        $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $action = str_replace(array($script_name.'/', $script_name.'?_='), '', $action);
        $action = trim($action, "/\n\r\0\t\x0B ");
        self::$config->action = $action;
        return self::$config;
    }
    
    public static function appdir(){
        return self::config()->appdir;
    }

    // the URL path should be the implicit path in the request URI.  We remove
    // the following for universal compatibility:
    // (note: all ending slashes are trimmed)
    // /foo/bar/                remove leading slash
    // /index.php/foo/bar       remove leading slash, then remove /index.php/
    // /index.php?_=            remove leading slash, index.php, and the special controller _=
    // if there is ?_=, use that first
    public function run(){        
        return self::dispatch( self::config()->action  );
    }
    
   /**
    * Alternate approach to the general dispatch method.
    * gets called if there is no entry in the config.
    */
    public static function dispatch( $name, $skip_render = FALSE ){
        $invoke = FALSE;
        $r = self::request();
        $data = $view = NULL;
        try {
            $controller = self::controller();
            $name = $controller->resolveRoute( $name );
            $data = $controller->execute( $name );
            if( $data === self::ABORT || $skip_render ) return $data;
            $view = self::view($data);
            return $view->render( $name );
        } catch( Exception $e ){
            if( $skip_render ) throw $e;
            if( ! $view ) $view = self::view($data);
            $view->set('exception', $e );
            return $view->render( $name .  '/' . $invoke . 'error');
        }
        return FALSE;
    }
    
    public static function controller( $data = NULL ){
        $class = self::config()->controller;
        return new $class($data);
    }
    
    public static function view( $data = NULL ){
        $class = self::config()->view;
        return new $class($data);
    }

    /**
    * Send the responsibility off to another action.
    * Return the outcome of the action.
    * @param string    Action Name.
    * @return mixed    An identifier for an action outcome.
    */
    public static function forward( $action_name ){
    	return self::dispatch( $action_name, TRUE );
    }
}