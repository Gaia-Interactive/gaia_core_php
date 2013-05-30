<?php

namespace Gaia\DB;
use Gaia\Exception;

class Except extends Wrapper {

    public function execute($query){
        $args = func_get_args();
        array_shift( $args );
        $query = $this->core->prep_args( $query, $args );
        $e = $rs = NULL;
        try {
            $rs = $this->core->execute($query);
        } catch( \Exception $e ){ }
        if( ! $rs ) {
            $msg = '';
            if( $this->core->isa('Gaia\DB\ExtendedIface') ) $msg .= ': ' . $this->core->error();
            throw new Exception('database error' . $msg, 
                                    array(
                                        'db'=> $this->core, 
                                        'query'=>$query, 
                                        'exception'=>$e ) );
        }
        return $rs;
    }
}