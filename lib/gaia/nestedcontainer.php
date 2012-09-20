<?php
namespace Gaia;
use Gaia\Container;

class NestedContainer extends Container implements \Iterator {
    
    public function all(){
        $all = array();
        foreach ($this as $k => $v){
            if ($v instanceof self ) $v = $v->all();
            $all[$this->key()] = $v;
        }
        return $all;
    }
    
    public function load( $input ){        
        if($input === NULL) return;        
        if(is_array($input) || $input instanceof parent) {
            foreach($input as $k=>$v){
                $this->__set( $k, $v);
            }
        }
    }

    public function set($k, $v){
        if (is_array($v) || $v instanceof parent) $v = new self($v);
        return parent::set($k, $v);
    }
}