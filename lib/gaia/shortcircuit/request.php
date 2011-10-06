<?php
namespace Gaia\ShortCircuit;

use Gaia\Container;

/**
 * CircuitRequest
 * @package CircuitMVC
 */
class Request extends Container implements Iface\Request
{

    private $args = array();
    
    private $uri = '/';
    
    private $action = '';

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
    public function getArgs() {
        return $this->args;
    }
    
    /**
    * set args into the request
    */
    public function setArgs( array $v ){
        return $this->args = $v;
    }
    
    public function action(){
        return $this->action;
    }
    
    public function uri(){
        return $this->uri;
    }
    
    /**
    * wrapper around the container accessor ... filtering the input values.
    * by default all values are filtered. you can access the raw data by doing:
    *   $request->get( $key, 'raw');
    */
    public function get( $key, $filter = 'safe', $default = NULL ){
        return $this->filter( parent::__get( $key ), $filter, $default );
    }
    
    /**
    * wrapper around the container accessor ... filtering the input values.
    */
    public function __get( $key ){
        return $this->filter( parent::__get( $key ) );
    }
    
    /**
     * apply basic input validation filter
     * @param filter
     *   available filters:
     *   'safe'     => remove '<', '>', '"', "'", '#', '&', '%', '{', and '('
     *   'posint'   => if not all digits, return default value
     *   'int'      => similar to postint, but negative allowed
     *   'alpha'    => if not all alphabets, return default value
     *   'alphanum' => if not all digits and alphabets, return default value
     *   'numeric'  => if not looks like a number, return default value
     *   'bool'     => evaluate $value as boolean, TRUE or FALSE is returned
     *   'regex'    => return $value if regex is matched, default value otherwise.
     *                 To use 'regex', pass in array('regex' => '/happy regex/i') for $filter
     *   'enum'    => return $value if regex is matched, default value otherwise.
     *                 To use 'enum', pass in array('enum' => array('a','b','c','d',...)) for $filter
     *   'raw'      => return $value untouched
     * @return scalar
     * @author llee
     */
    public static function filter($value, $filter = 'safe', $default = NULL ) {
        if ( is_array($filter) ) {
            switch(key($filter)) {
            case 'regex':
                $pattern = $filter['regex'];
                $filter = 'regex';
                break;
            case 'enum':
                $pattern = $filter['enum'];
                $filter = 'enum';
                break;
            default:
                // nothing...
                break;
            }
        }

        switch ($filter) {
        case 'posint':
            return ctype_digit(strval($value)) ? $value : $default;
        case 'int':
            return (ctype_digit(strval($value)) ||
                      ($value[0] == '-' && ctype_digit(substr($value, 1)))
                      )
                 ? $value : $default;
        case 'alpha':
            return ctype_alpha(strval($value)) ? $value : $default;
        case 'alphanum':
            return ctype_alnum(strval($value)) ? $value : $default;
        case 'numeric':
            return is_numeric($value) ? $value : $default;
        case 'bool':
            return $value ? TRUE : FALSE;
        case 'raw':
            return $value;
        case 'enum':
            return in_array($value, $pattern) ? $value : $default;
        case 'regex':
            return preg_match($pattern, $value) ? $value : $default;
        case 'safe':
        default:
            $unsafe = array('<', '>', '"', "'", '#', '&', '%', '{', '(');
            if( is_array( $value ) ){
                foreach( $value as $k=>$v ) $value[ $k ] = str_replace($unsafe, '', strval($v));
            } else {
                $value = str_replace($unsafe, '', strval($value));
            }
            // set to default value if there is nothing left after filtering
            return $value ? $value : $default;
        }
    }
}
