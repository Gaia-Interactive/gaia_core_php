<?php
namespace Gaia\Cache;

class Observe extends Wrap {

    protected $_calls = array(); 
    function decrement($k, $v = 1){$args = func_get_args();  return $this->__call( __FUNCTION__, $args );}
    function flush(){ $args = func_get_args(); return $this->__call( __FUNCTION__, $args );}
    function delete($key) { $args = func_get_args(); return $this->__call( __FUNCTION__, $args );}
    function get($input, $options = NULL){$args = func_get_args(); return $this->__call( __FUNCTION__, $args );}
    function increment($k, $v = 1){ $args = func_get_args(); return $this->__call( __FUNCTION__, $args );}
    function replace($k, $v, $f = 0, $e = 0){$args = func_get_args();  return $this->__call( __FUNCTION__, $args );}
    function set($k, $v, $e = 0){$args = func_get_args(); return $this->__call( __FUNCTION__, $args ); }
    function add($k, $v, $e = 0){ $args = func_get_args(); return $this->__call( __FUNCTION__, $args );}
    
    function __call($method, $args){ 
        $res = call_user_func_array( array( $this->core, $method), $args);
        $this->_calls[] = array('method'=>$method, 'args'=>$args, 'result'=>$res);
        return $res;
    }

    public function calls(){
        $calls = $this->_calls;
        $this->_calls = array();
        return $calls;
    }
}

