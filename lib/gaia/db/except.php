<?php

namespace Gaia\DB;
use Gaia\Exception;

class Except extends Wrapper {

    public function execute($query){
        $args = func_get_args();
        array_shift( $args );
        $query = $this->core->format_query_args( $query, $args );
        $e = $rs = NULL;
        try {
            $rs = $this->core->execute($query);
        } catch( \Exception $e ){ }
        if( ! $rs ) {
            throw new Exception('database error', 
                                    array(
                                        'db'=> $this->core, 
                                        'query'=>$query, 
                                        'exception'=>$e ) );
        }
        return $rs;
    }
}