<?php
namespace Gaia;
use Gaia\Container;

class NestedContainer extends Container implements \Iterator {
   
   public function all(){
       $all = array();
       foreach ($this as $k => $v){
           if ($v instanceof parent ) $v = $v->all();
           $all[$k] = $v;
       }
       return $all;
   }
   
   public function set($k, $v){
       if (is_array($v) || $v instanceof \Iterator) $v = new self($v);
       return parent::set($k, $v);
   }
}