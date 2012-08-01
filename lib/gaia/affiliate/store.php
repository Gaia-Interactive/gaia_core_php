<?php
namespace Gaia\Affiliate;
use Gaia\Store as Core;
use Gaia\Exception;

class Store implements Iface {

    protected $core;

    public function __construct( Core\Iface $core ){
        $this->core = $core;
    }
    
    public function affiliations( array $identifiers ){
        return $this->identifierStore()->get( $identifiers );
    }
        
    public function identifiers( array $affiliate_ids ){
        return $this->affiliateStore()->get( $affiliate_ids );
    }
    
    public function related( array $identifiers ){
        return Util::related( $this, $identifiers );
    }
    
    public function join( array $identifiers ){
        return $this->_joinRelated( $this->related($identifiers) );
    }
    
    public function _joinRelated( array $related ){
        $affiliate = NULL;
        foreach( $related as $identifier => $affiliate ){
            if( $affiliate ) break;            
        }
        
        if( ! $affiliate ) $affiliate = Util::newID();
        $store = $this->identifierStore();
        
        $remove = array();
        foreach( $related as $identifier => $_id ){
            $related[ $identifier ] = $affiliate;
            $store->set( $identifier, $affiliate );
            if( $_id && $_id != $affiliate ) $remove[$_id] = $_id;
        }
        
        $store = $this->affiliateStore();
        
        foreach( $remove as $_id ){
            $store->delete( $_id );
        }        
        $store->set( $affiliate, array_keys( $related ) );
        
        return $related;
        
    }
    
    public function delete( array $identifiers ){
        $affiliate_ids = array_unique( array_values( $this->affiliations( $identifiers) ) );
        
        $store = $this->affiliateStore();
        foreach( $this->identifiers( $affiliate_ids ) as $affiliate_id => $ids ){
            foreach( $ids as $i => $id ){
                if( in_array( $id, $identifiers ) ){
                    unset( $ids[ $i ] );
                }
            }
            $ids = array_values( $ids );
            if( ! $ids ) {
                $store->delete($affiliate_id );
            } else {
                $store->set($affiliate_id, $ids );
            }
        }
        $store = $this->identifierStore();
        foreach( $identifiers as $identifier ){
            $store->delete( $identifier );
        }
    }
    
    protected function identifierStore(){
        return new Core\Prefix( $this->core, 'identifiers/');
    }
    
    protected function affiliateStore(){
        return new Core\Prefix( $this->core, 'affiliates/');
    }
}
