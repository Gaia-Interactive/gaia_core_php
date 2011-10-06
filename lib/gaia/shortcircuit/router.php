<?php 
namespace Gaia\ShortCircuit;
use Gaia\Container;
use Gaia\Exception;

/**
 * Routes requests to the correct shortcircuit object, performs the action, and routes
 * the response to a view to render the output.
 * This class is static and can be accessed from anywhere throughout the lifetime of the request.
 * this is the only static class in the framework. the rest are instantiated and attached to this
 * static class.
 */
class Router {
    
    /*
    * a return value of the controller to indicate we no longer should proceed with processing.
    */
    const ABORT = '__ABORT__';
    
    /*
    * a Request object ... used to access $_REQUEST variables.
    */
    protected static $request;
    
    /**
    * the config object, where we can change runtime behavior of ShortCircuit
    */
    protected static $config;
    
    /*
    * the controller object.
    */
    protected static $controller;
    
    /**
    * the view object
    */
    protected static $view;
    
    /**
    * the resolver object.
    */
    protected static $resolver;
    
    /**
    * request object.
    */
    public static function request(){
        if( isset( self::$request ) ) return self::$request;
        return self::$request = new Request( $_REQUEST );
    }
    
    /**
    * grab the singleton config object.
    * on first access, set up some defaults.
    */
    public static function config(){
        if( isset( self::$config ) ) return self::$config;
        self::$config = new Container();
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
        if( ! $action ) $action = '/';
        self::$config->action = $action;
        return self::$config;
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
    * make it easy to access the app dir in the resolver
    */
    public function appdir(){
        return self::resolver()->appdir();
    }

   /**
    * make it easy to set the app dir in the resolver
    */
    public function setAppDir( $dir ){
        self::resolver()->setAppDir( $dir );
    }
    
   /**
    * Alternate approach to the general dispatch method.
    * gets called if there is no entry in the config.
    */
    public static function dispatch( $input, $skip_render = FALSE ){
        $invoke = FALSE;
        $r = self::request();
        $data = $view = NULL;
        try {
            $controller = self::controller();
            $name = $controller->resolveRoute( $input );
            if( strpos($input, $name) !== FALSE ) {
                $r->set('__args__', explode('/', substr($input, strlen($name)+1)));
            }
            $data = $controller->execute( $name );
            if( $data === self::ABORT || $skip_render ) return $data;
            $view = self::view();
            $view->load( $data );
            return $view->render( $name );
        } catch( Exception $e ){
            if( $skip_render ) throw $e;
            if( ! $view ) $view = self::view();
            $view->load( $data );
            $view->set('exception', $e );
            return $view->render( $name .  '/' . $invoke . 'error');
        }
        return FALSE;
    }
    
    /**
    * get the singleton controller ojbect. Can pipe data into it,
    * though we really don't often need to do that.
    * customize by doing:
    *   Router::controller( new MyController );
    */
    public static function controller( $controller = NULL ){
        if( is_object( $controller ) ) return self::$controller = $controller;
        if( isset( self::$controller ) ) return self::$controller;
        return self::$controller = new Controller();
    }
    
    /*
    * grab the view object.
    * customize by doing:
    *   Router::view( new MyView );
    */
    public static function view( $view = NULL ){
        if( is_object( $view ) ) return self::$view = $view;
        if( isset( self::$view ) ) return self::$view;
        return self::$view = new View();
    }
    
    /**
    * get the singleton resolver object. This object decides how to resolve the 
    * names and uris to files.
    * customize by doing:
    *   Router::resolver( new MyResolver );
    */
    public static function resolver( $resolver = NULL ){
        if( is_object( $resolver ) ) return self::$resolver = $resolver;
        if( isset( self::$resolver ) ) return self::$resolver;
        return self::$resolver = new Resolver();
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

// EOF