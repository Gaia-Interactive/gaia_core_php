<?php
namespace Gaia\APN;

class AppNotice extends WrapNotice {
    
    public function setApp( $v ){
        $this->set('app', $v);
    }
    
    public function getApp(){
        return $this->get('app');
    }
}
