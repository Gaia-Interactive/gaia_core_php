<?php
namespace Gaia\Store;

class Ref extends KVP {
    public function __construct( array & $data ){
        $this->__d =& $data;
    }

}