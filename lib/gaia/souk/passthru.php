<?php
namespace Gaia\Souk;
use Gaia\Exception;

/**
 * @package GAIAONLINE
 * @copyright 2003-present GAIA Interactive, Inc.
 * @license private/license/GAIAONLINE.txt
 */

class Passthru implements Iface {
    protected $core;
    protected $own_tran = FALSE;

    public function __construct( Iface $core ){
        $this->core = $core;
    }
    public function app(){
        return $this->core->app();
    }
    public function user(){
        return $this->core->user();
    }
    public function auction( $l, array $data = NULL ){
        return $this->core->auction( $l, $data );
    }
    public function close( $id, array $data = NULL ){
        return $this->core->close( $id, $data );
    }
    public function buy($id, array $data = NULL ){
        return $this->core->buy( $id, $data );
    }
    public function bid( $id, $bid, array $data = NULL ){
        return $this->core->bid( $id, $bid, $data );
    }
    public function get( $id, $lock = FALSE ){
        $res = $this->fetch( array( $id ), $lock );
        if( ! isset( $res[ $id ] ) ) {
            if( $lock ) throw new Exception('not found');
            return NULL;
        }
        return $res[ $id ];
    }
    public function fetch( array $ids, $lock = FALSE){
        return $this->core->fetch( $ids, $lock );
    }
    public function search( $options ){
        return $this->core->search( $options );
    }
    public function pending( $age = 0, $limit = 1000, $offset_id = 0 ){
        return $this->core->pending( $age, $limit, $offset_id );
    }

}
// EOF
