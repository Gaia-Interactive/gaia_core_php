<?php
namespace Gaia;

class Container implements \Iterator {
 /**
    * internal data storage
    */
    protected $__d = array();
    
    public function __construct( $input = NULL ){
        $this->load( $input );
    }
    
    public function set($name, $value){
        return $this->__d[ $name ] = $value;
    }
    
    public function increment($name, $value = 1) {
        if(! isset($this->__d[$name]) ) $this->__d[$name] = 0;
        return $this->__d[$name] += $value;
    }
    
    public function decrement($name, $value = 1) {
        if(! isset($this->__d[$name]) ) $this->__d[$name] = 0;
        return $this->__d[$name] -= $value;
    }
    
    public function add( $name, $value, $ttl = 0 ){
        if( $this->__isset( $name ) ) return FALSE;
        return $this->set( $name, $value, $ttl );
    }
    
    public function replace( $name, $value, $ttl = 0 ){
        if( ! $this->__isset( $name ) ) return FALSE;
        return $this->set( $name, $value, $ttl );
    }
    
    public function append($name, $value){
        if( ! isset($this->__d[$name]) ) return $this->__d[$name] = array($value);
        if( is_scalar($this->__d[$name]) ) return $this->__d[$name] .= $value;
        if( ! is_array($this->__d[$name]) ) return $this->__d[$name] = array($value);
        return $this->__d[$name][] = $value;
    }
    
    public function get($name){
        if( is_array( $name ) ){
            $res = array();
            foreach( $name as $_k ){
                $v = $this->__get( $_k );
                if( $v === NULL ) continue;
                $res[ $_k ] = $v;
            }
            return $res;
        }
        if( ! is_scalar( $name ) ) return NULL;
        return isset( $this->__d[ $name ] ) ? $this->__d[ $name ] : NULL;

    }
    
    public function delete($name){
        unset( $this->__d[ $name ] );
        return TRUE;
    }
    
    public function flush(){
        $this->__d = array();
    }
    
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
        if( $key === FALSE ) return FALSE;
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
        $keys = $this->keys();
        return $this->{count( $keys ) > 0 ? max($keys) + 1 : 0} = $v;
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
        $keys = $this->keys();
        return $this->{count( $keys ) > 0 ? min($keys) -1 : 0} = $v;
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
    
   /**
    * @see http://www.php.net/manual/en/function.array-values.php
    **/
    public function values(){
        return array_values( $this->__d );
    }
    
   /**
    * @see http://www.php.net/oop5.magic
    */
    public function __set( $k, $v ){
        return $this->set( $k, $v );
    }
    
   /**
    * @see http://www.php.net/oop5.magic
    */
    public function __get( $k ){
        return $this->get( $k );
    }
    
   /**
    * @see http://www.php.net/oop5.magic
    */
    public function __unset( $k ){
        $this->delete( $k );
    }
    
   /**
    * @see http://www.php.net/oop5.magic
    */
    public function __isset( $k ){
        return ( $this->get( $k ) !== NULL ) ? TRUE  : FALSE;
    }
     
         
   /**
    * if we try to print the object, give something easier to scan.
    */
    public function __toString(){
        $out = get_class( $this ) . " {\n";
        foreach( $this->__d as $k=>$v ){
            if( ! is_scalar( $v ) ) $v = print_r( $v, TRUE);
            if( ( $len = strlen( $v ) ) > 100 ) $v = substr($v, 0, 100) . '... (' . $len . ')';
            $v = str_replace("\n", '\n',  str_replace("\r", '\r', $v));
            $out .= '    [' . $k . '] => ' . $v . "\n";
        }
        $out .= "}\n";
        return $out;
    }
}
