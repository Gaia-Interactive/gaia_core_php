<?php
namespace Gaia;
use Gaia\Store\KVP;

class Container extends KVP implements \Iterator {
    
    public function isEmpty( $name ){
        if( ! isset( $this->__d[$name] ) ) return TRUE;
        if( empty( $this->__d[$name] ) ) return TRUE;
        return FALSE;
    }
    
    public function all(){
        return $this->__d;
    }
    
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->__set( $k, $v);
        }
        // all done.
    }
    
   /**
    * @see http://www.php.net/manual/en/language.oop5.iterations.php
    **/
    public function current() {
        return current($this->__d);
    }
    
   /**
    * @see http://www.php.net/manual/en/language.oop5.iterations.php
    **/
    public function key() {
        return key($this->__d);
    }
    
   /**
    * @see http://www.php.net/manual/en/language.oop5.iterations.php
    **/
    public function next() {
        return next($this->__d);
    }
    
   /**
    * @see http://www.php.net/manual/en/language.oop5.iterations.php
    **/
    public function valid() {
        return ($this->key() !== NULL);
    }
    
   /**
    * @see http://www.php.net/manual/en/language.oop5.iterations.php
    **/
    public function rewind() {
        reset($this->__d);
    }
    
    public function each(){
        $key = $this->key();
        if( $key === NULL ) return FALSE;
        $this->next();
        return array( $key, $this->get($key) );
    }
    
   /**
    * @see http://www.php.net/manual/en/function.count.php
    **/
    public function count() {
        return count($this->__d);
    }
    
   /**
    * @see http://www.php.net/manual/en/function.array-keys.php
    **/
    public function keys(){
        $args = func_get_args();
        if( count($args) < 1 ) return array_keys( $this->__d);
        $search = array_shift( $args );
        $strict = array_shift( $args );
        return array_keys( $this->__d, $search, $strict);
    }
    
   /**
    * @see http://www.php.net/manual/en/function.array-push.php
    **/
    public function push($v){
        return array_push($this->__d, $v );
    }
    
   /**
    * @see http://www.php.net/manual/en/function.array-pop.php
    **/
    public function pop(){
        return array_pop($this->__d);
    }
    
   /**
    * @see http://www.php.net/manual/en/function.array-shift.php
    **/
    public function shift(){
        return array_shift($this->__d);
    }
    
   /**
    * @see http://www.php.net/manual/en/function.array-unshift.php
    **/
    public function unshift($v){
        return array_unshift( $this->__d, $v );
    }
    
   /**
    * @see http://www.php.net/manual/en/function.asort.php
    **/
    public function sort($sort_flags = NULL){
        return asort($this->__d, $sort_flags );
    }
    
   /**
    * @see http://www.php.net/manual/en/function.arsort.php
    **/
    public function rsort($sort_flags = NULL){
        return arsort($this->__d, $sort_flags );
    }
   
   /**
    * @see http://www.php.net/manual/en/function.ksort.php
    **/
    public function ksort($sort_flags = NULL){
        return ksort($this->__d, $sort_flags);
    }
    
   /**
    * @see http://www.php.net/manual/en/function.ksort.php
    **/
    public function krsort($sort_flags = NULL){
        return krsort($this->__d, $sort_flags);
    }
}
