<?php
namespace Gaia\Store;
use Gaia\Container;

class Internal extends Container implements Iface {
    
    public function supportsTTL(){
        FALSE;
    }

}
