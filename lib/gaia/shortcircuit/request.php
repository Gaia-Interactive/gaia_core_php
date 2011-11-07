<?php
namespace Gaia\ShortCircuit;
/**
 * CircuitRequest
 * @package CircuitMVC
 */
class Request extends Input implements Iface\Request
{

    private $args = array();
    
    private $uri = '/';
    
    private $action = '';
    
    private $base = '';

   /**
    * Class constructor.
    * pass in alternate to $_REQUEST
    */
    public function __construct( $data = NULL ){
        if( $data === NULL ) $data = $_REQUEST;
        parent::__construct( $data );
        $trim_chars = "/\n\r\0\t\x0B ";
        $this->uri = isset($_SERVER['REQUEST_URI']) ? '/' . trim($_SERVER['REQUEST_URI'], $trim_chars) : '/';
        if (isset($this->{'_'})) {
            $action = $this->{'_'};
        }
        else if (isset($_SERVER['PATH_INFO'])) {
            $action = $_SERVER['PATH_INFO'];
        }
        else {
            $pos = strpos($this->uri, '?');
            $action =( $pos === FALSE ) ? 
                $this->uri : substr($this->uri , 0, $pos);
        }
        $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $action = str_replace(array($script_name.'/', $script_name.'?_='), '', $action);
        $action = trim($action, $trim_chars);
        $this->action = '/' . $action;
          if (strpos($this->uri, $script_name) === 0) {
            $this->base = $script_name;
        } else {
            $this->base = '';
        }
    }
    
    /**
    * grab the action set in the constructor
    */
    public function action(){
        return $this->action;
    }
    
  /**
    * grab the uri set in the constructor
    */
    public function uri(){
        return $this->uri;
    }
    
    public function base(){
        return $this->base;
    }
}
