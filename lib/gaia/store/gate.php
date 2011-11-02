<?php
namespace Gaia\Store;
use Gaia\Time;

class Gate extends Wrap {

    const DEFAULT_TTL = 259200;
    
    public function get( $request ){
        if( is_array( $request ) ) return $this->getMulti( $request );
        if( ! is_scalar( $request ) ) return NULL;
        $res = $this->getMulti( array( $request ) );
        //var_dump( $res );
        if( ! isset( $res[ $request ] ) ) return NULL;
        return $res[ $request ];
    }
    
    protected function getMulti( $keys ){
        if( ! is_array($keys ) ) return FALSE;
        foreach( $keys as $k ) {
            $matches[ $k ] = NULL;
            $matches[ $exp_keys[] = $k .  '/__exp/' ] = NULL;
        }
        $res = $this->core->get( array_keys( $matches ) );
        if( ! is_array( $res ) ) $res = array();
        foreach( $res as $k=>$v ){
            $matches[ $k ] = $v;
        }
        
        $now = Time::now();
        foreach( $exp_keys as $k ){
            if( ! isset( $matches[ ($parent_key = substr( $k,0, -7)) ] ) ) continue;
            $diff = ( ctype_digit( (string) $matches[ $k ] ) ) ? $matches[ $k ] - $now : 0;
            if( $diff > 0 && // if the soft ttl is too old, reset it
                mt_rand(1, pow( $diff, 3) ) != 1 // randomly reset it based on a parabolic curve approaching timeout.
            ) continue;
            if( ! $this->core->add( $k . '/__r-lock', 1, 5) && 
                  $this->core->get( $k . '/__r-lock') ) continue;
            if( $diff < 10 ){
                $this->core->set($k, $now + 10);
            }
            unset( $matches[ $parent_key ] );
        }
        $res = array();
        foreach( $keys as $k ){
            if( ! isset( $matches[ $k ] ) ) continue;
            $res[ $k ] = $matches[ $k ];
        }        
        return $res;
    }
    
    function set($k, $v, $expire = 0 ){
        $this->core->set($k . '/__lock/', 1, $expire );
        if( ! $expire ) $expire = self::DEFAULT_TTL;
        $res = $this->core->set($k, $v);
        $this->core->set($k . '/__exp/', Time::now() + $expire);
        return $res;
    }
    
    function delete( $k ){
        $this->core->delete($k . '/__lock/' );
        $this->core->delete($k . '/__exp/' );
        return $this->core->delete($k);
    }   
    
    function add($k, $v, $expire = 0 ){
        if( ! $this->core->add($k . '/__lock/', 1, $expire ) ) return FALSE;
        if( ! $expire ) $expire = self::DEFAULT_TTL;
        $this->core->set($k . '/__exp/', Time::now() + $expire);
        return $this->core->set($k, $v );
    }
    
    function replace($k, $v, $expire = 0 ){
        if( ! $this->core->replace( $k . '/__lock/', 1, $expire ) ) return FALSE;
        if( ! $expire ) $expire = self::DEFAULT_TTL;
        $this->core->set($k . '/__exp/', Time::now() + $expire);
        return $this->core->set($k, $v );
    }
    
    function increment($k, $v = 1){
        if( ! $this->core->get($k . '/__exp/' ) ) return FALSE;
        return $this->core->increment($k, $v );
    }
    
    function decrement($k, $v = 1){
        if( ! $this->core->get($k . '/__exp/' ) ) return FALSE;
        return $this->core->decrement($k, $v );
    }
}
