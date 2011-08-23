<?php 
namespace Gaia\ShortCircuit;
use Gaia\Container;
use Gaia\Exception;


/**
 * The controller object dispatches the request and is responsible
 * for instantiating the models, views, actions and frames.
 * @package CircuitMVC
 * be sure to define DIR_APP_SHORTCIRCUIT
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
        if( defined('SHORTCIRCUIT_APPDIR' ) ) self::$config->appdir = SHORTCIRCUIT_APPDIR;
        self::$config->view = 'Gaia\ShortCircuit\View';
        return self::$config;
    }
    
    public static function appdir(){
        return self::config()->appdir;
    }


    public function run(){        
        // the URL path should be the implicit path in the request URI.  We remove
        // the following for universal compatibility:
        // (note: all ending slashes are trimmed)
        // /foo/bar/                remove leading slash
        // /index.php/foo/bar       remove leading slash, then remove /index.php/
        // /index.php?_=            remove leading slash, index.php, and the special controller _=
        // if there is ?_=, use that first
        $r = self::request();
        if (isset($r->{'_'})) {
            $url_path = $r->{'_'};
        }
        else if (isset($_SERVER['PATH_INFO'])) {
            $url_path = $_SERVER['PATH_INFO'];
        }
        else {
            $pos = strpos($_SERVER['REQUEST_URI'], '?');
            $url_path =( $pos === FALSE ) ? 
                $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $pos);
        }
        
        $script_name = $_SERVER['SCRIPT_NAME'];
        $url_path = str_replace(array($script_name.'/', $script_name.'?_='), '', $url_path);
        $url_path = trim($url_path, "/\n\r\0\t\x0B ");
        
        if( self::dispatch( $url_path ) !== FALSE ) return;
        
    }
    
   /**
    * Alternate approach to the general dispatch method.
    * gets called if there is no entry in the config.
    */
    public static function dispatch( $name, $skip_render = FALSE ){
        $invoke = FALSE;
        $r = self::request();
        $shortcircuit = $fallback = FALSE;
        $target = '';
        try {
            $args = explode('/', $name);
            while( $a = array_shift($args) ){
                if( strlen( $a ) < 1 ) continue;
                if( strlen( $target ) > 0 ) $target .= '\\';
                $target .= $a;
                $class = $target . '\shortcircuit';
                $shortcircuit = class_exists( $class ) ? $class : FALSE;
                if( ! $shortcircuit ){
                    array_unshift( $args, $a);
                    break;
                }
                $fallback = $shortcircuit;
                $invoke = array_shift( $args );
                if( ! $invoke ) break;
                if( method_exists( $shortcircuit, $invoke . 'action') ) break;
                array_unshift( $args, $invoke);
                $invoke = FALSE;
            }
            if( ( $ct = count($args) ) > 0 ){
                for( $i = 0; $i < $ct; $i+=2){
                    if( ! isset( $args[ $i+1 ]) ) continue;
                    $r->set( $args[$i], $args[ $i+1 ]);
                }
                
            }
            if( ! $shortcircuit ) $shortcircuit = $fallback;
            if( ! $invoke ) $invoke = 'index';
            if( ! $shortcircuit || ! method_exists( $shortcircuit, $invoke . 'action')) return FALSE;
            $r->set('__args__', $args );
            $name = str_replace('\\', '/', substr($shortcircuit, 0, -12));
            $viewclass = self::config()->view;
            $view = new $viewclass();
            $shortcircuit = new $shortcircuit();
            $data = $shortcircuit->{$invoke . 'action'}( $r );
            if( $data === self::ABORT ) return;
            if( is_array( $data ) ) {
                foreach( $data as $k=>$v) $view->set($k, $v);
            } elseif( $data instanceof Container ) {
                foreach( $data->all() as $k=>$v) $view->set($k, $v);
            } else {
                $view->set('result', $data);
            }
            if( $skip_render ) return $view->all();
            return $view->render( $name . '/' . $invoke );
        } catch( Exception $e ){
            if( $skip_render ) throw $e;
            if( ! $view ) $view = new View();
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
}

spl_autoload_register(function($class) {
    $class = strtolower( $class );
    if( ! preg_match('/^([a-z][\\a-z0-9_]+)shortcircuit$/', $class, $matches ) ) return;
    $path = Router::appdir() . str_replace('\\', '/', strtolower($matches[1])) . 'shortcircuit.php';
    if( ! file_exists( $path ) ) return FALSE;
    include $path;
});
