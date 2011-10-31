<?php
namespace Gaia\Serialize;

class PHP implements Iface {
    public function serialize( $v ){
        return serialize($v);
    }
    public function unserialize( $v ){
        return unserialize($v);
    }
}