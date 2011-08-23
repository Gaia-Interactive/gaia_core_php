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
    const UNDEF = '__UNDEF__';
    
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
            $args = explode('/', $name);
            $r->set('__args__', $args );
            $controllerclass = self::config()->controller;
            $controller = new $controllerclass();
            do{
                $n = implode('/', $args );
                $path = self::resolve( $n, 'action');
                if( ! $path ) continue;
                $data = $controller->execute( $n );
                if( $data === self::ABORT || $skip_render ) return $data;
                $viewclass = self::config()->view;
                $view = new $viewclass($data);
                return $view->render( $n );
            } while( array_pop( $args ) );
        } catch( Exception $e ){
            if( $skip_render ) throw $e;
            if( ! $view ){
                $viewclass = self::config()->view;
                $view = new $viewclass($data);
            }
            $view->set('exception', $e );
            return $view->render( $name .  '/' . $invoke . 'error');
        }
        return FALSE;
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
    
    public static function resolve($name, $type ) {
        $name = strtolower($name);
        $type = strtolower($type);
        if( strpos($name, '.') !== FALSE ) return FALSE;
        $dir =  Router::appdir();
        $apc_key = 'shortcircuit/' . $type . '/' . $dir . '/' . $name;
        $path = apc_fetch( $apc_key );
        if( $path == self::UNDEF ) return '';
        if( $path ) return $path;
        $path = $dir . $name . '.' . $type . '.php';
        if( ! file_exists( $path ) ) $path = $dir . $name . '/index.' . $type . '.php';
        if( ! file_exists( $path ) ) $path = self::UNDEF;
        apc_store( $apc_key, $path, $path != self::UNDEF ? 300 : 60 );
        return ( $path != self::UNDEF ) ? $path : '';
    } 
    
}