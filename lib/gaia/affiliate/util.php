<?php
namespace Gaia\Affiliate;
use Gaia\NewId;

class Util {
    
    public static function newId(){
         $creator = new NewId\TimeRand();
        return $creator->id();
    }
    
    public static function findRelated( Iface $core, array $identifiers ){
        $result = array_fill_keys( $identifiers, NULL );
        foreach( $core->get( array_unique( array_values( $core->search( $identifiers) ) ) ) as $astral_id => $identifiers ){
            foreach( $identifiers as $identifier ) $result[ $identifier ] = $astral_id;
        }
        return $result;
    }
}

