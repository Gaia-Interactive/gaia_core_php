<?php
namespace Gaia\Serialize;

class JSON implements Iface {
    public function serialize( $v ){
        return json_encode($v);
    }
    public function unserialize( $v ){
        return json_decode($v, TRUE);
    }
}