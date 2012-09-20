<?php
namespace Gaia;
use Gaia\Container;

class NestedContainer extends Container implements \Iterator {
    
   public function all(){
        for ($this->rewind(); $this->valid(); $this->next()){
            if ($this->current() instanceof NestedContainer ){
                $v = $this->current()->all();
            }else{
                $v = $this->current();
            }
            $all[$this->key()] = $v;
        }
        return $all;
    }
    
    public function load( $input ){
        
        if( $input === NULL ) return;
        
        if( is_array( $input ) || $input instanceof NestedContainer ) {
            foreach( $input as $k=>$v ){
                if( is_array( $v )) $v = new NestedContainer($v);
                $this->__set( $k, $v);
            }
        }
    }
}