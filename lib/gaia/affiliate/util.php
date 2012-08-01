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
        foreach( $core->identifiers( array_unique( array_values( $core->affiliations( $identifiers) ) ) ) as $affiliation => $identifiers ){
            foreach( $identifiers as $identifier ) $result[ $identifier ] = $affiliation;
        }
        return $result;
    }
}

