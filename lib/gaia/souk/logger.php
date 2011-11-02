<?php
namespace Gaia\Souk;
use Gaia\Exception;

class Logger extends Passthru {
    
    protected $handler;
    
    public function __construct( Iface $core, $handler ){
        if( ! is_callable( $handler ) ) throw new Exception('invalid logging handler', $handler);
        $this->handler = $handler;
        parent::__construct($core);
    }
    
    public function auction( $l, array $data = NULL ){
        $listing = $this->core->auction( $l, $data );
        $this->log( $listing, __FUNCTION__ );
        return $listing;
    }
    public function close( $id, array $data = NULL ){
        $listing = $this->core->close( $id, $data );
        $this->log( $listing, __FUNCTION__ );
        return $listing;
    }
    public function buy($id, array $data = NULL ){
        $listing = $this->core->buy( $id, $data );
        $this->log( $listing, __FUNCTION__ );
        return $listing;
    }
    public function bid( $id, $bid, array $data = NULL ){
        $listing = $this->core->bid( $id, $bid, $data );
        $this->log( $listing, __FUNCTION__ );
        return $listing;
    } 
    
    protected function log( Listing $listing, $action ){
        call_user_func( $this->handler, $action, $listing );
    }
}
// EOF
