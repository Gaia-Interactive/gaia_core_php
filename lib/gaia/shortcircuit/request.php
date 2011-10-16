<?php
namespace Gaia\ShortCircuit;

use Gaia\Input;

/**
 * CircuitRequest
 * @package CircuitMVC
 */
class Request extends Input implements Iface\Request
{

    private $args = array();
    
    private $uri = '/';
    
    private $action = '';

   /**
    * Class constructor.
    * pass in alternate to $_REQUEST
    */
    public function __construct( $data = NULL ){
        if( $data === NULL ) $data = $_REQUEST;
        parent::__construct( $data );
        
        $this->uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
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
        $action = trim($action, "/\n\r\0\t\x0B ");
        if( ! $action ) $action = '/';
        $this->action = $action;
    }
   
   /**
    * get the args
    * @return array
    * @access protected
    */
    public function args() {
        return $this->args;
    }
    
   /**
    * set args into the request
    */
    public function setArgs( array $v ){
        return $this->args = $v;
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
}
