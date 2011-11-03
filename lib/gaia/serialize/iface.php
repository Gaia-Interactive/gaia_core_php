<?php
namespace Gaia\Serialize;

interface Iface {
    public function serialize( $v );
    public function unserialize( $v );
}