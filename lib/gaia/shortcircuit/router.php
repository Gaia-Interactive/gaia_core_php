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
    


    // the URL path should be the implicit path in the request URI.  We remove
    // the following for universal compatibility:
    // (note: all ending slashes are trimmed)
    // /foo/bar/                remove leading slash
    // /index.php/foo/bar       remove leading slash, then remove /index.php/
    // /index.php?_=            remove leading slash, index.php, and the special controller _=
    // if there is ?_=, use that first
    public static function run(){        
        return self::dispatch( self::request()->action()  );
    }
    
   /**
    * make it easy to access the app dir in the resolver
    */
    public static function appdir(){
        return self::resolver()->appdir();
    }

   /**
    * make it easy to set the app dir in the resolver
    */
    public static function setAppDir( $dir ){
        self::resolver()->setAppDir( $dir );
    }
    
   /**
    * take a route name, and run it
    */
    public static function dispatch( $input, $skip_render = FALSE ){
        $invoke = FALSE;
        $r = self::request();
        $data = $view = NULL;
        try {
            $controller = self::controller();
            $name = self::resolver()->match( $input, $args );
            if( ! $name ) $name = '404';
            if( is_array( $args ) ){
                foreach( $args as $k => $v ){
                    if( ! is_int( $k ) ) {
                        unset( $args[ $k ] );
                        $r->set( $k, $v );
                    }
                }
                $r->setArgs( $args );
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
    
    public static function link( $name, array $params = array() ){
        return self::request()->base() . self::resolver()->link( $name, $params );
    }
    
    /**
    * get the singleton request object.
    * can customize by doing:
    * Router::request( new MyRequest );
    */
    public static function request( Iface\Request $request = NULL){
        if( $request ) return self::$request = $request;
        if( isset( self::$request ) ) return self::$request;
        return self::$request = new Request();
    }
    
    /**
    * get the singleton controller ojbect. Can pipe data into it,
    * though we really don't often need to do that.
    * customize by doing:
    *   Router::controller( new MyController );
    */
    public static function controller( Iface\Controller $controller = NULL ){
        if( $controller ) return self::$controller = $controller;
        if( isset( self::$controller ) ) return self::$controller;
        return self::$controller = new Controller();
    }
    
    /*
    * grab the view object.
    * customize by doing:
    *   Router::view( new MyView );
    */
    public static function view( Iface\View $view = NULL ){
        if( $view ) return self::$view = $view;
        if( isset( self::$view ) ) return self::$view;
        return self::$view = new View();
    }
    
    /**
    * get the singleton resolver object. This object decides how to resolve the 
    * names and uris to files.
    * customize by doing:
    *   Router::resolver( new MyResolver );
    */
    public static function resolver( Iface\Resolver $resolver = NULL ){
        if( $resolver ){
            if( self::$resolver && ! $resolver->appdir() ){
                $resolver->setAppDir( self::$resolver->appdir() );
            }
            return self::$resolver = $resolver;
        }
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