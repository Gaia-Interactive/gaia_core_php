<?php
namespace Gaia\Store;

class Signed extends Wrap {

    private $__secret;
    
    public function __construct( Iface $core, $secret ){
        parent::__construct( $core );
        $this->__secret = $secret;
    }
    
    public function get( $k ){
        if( is_array( $k ) ){
            $res = array();
            foreach( $this->core->get($k) as $_k => $v){
                $v = $this->unserialize( $v );
                if( $v !== FALSE && $v !== NULL ) $res[ $_k ] = $v;
            }
            return $res;
        }
        return $this->unserialize( $this->core->get( $k ) );
    }
    
    public function set( $k, $v ){
        $this->core->set( $k, $this->serialize( $v ) );
        return $v;
    }

    public function add( $k, $v, $expires = NULL ){
        return $this->core->add( $k, $this->serialize($v), $expires );
    }
    
    public function replace( $k, $v, $expires = NULL ){
        return $this->core->replace( $k, $this->serialize($v), $expires );
    }
    
    public function increment( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === FALSE ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        return $this->set( $k, bcadd( $v, $step ));
    }
    
    public function decrement( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === FALSE ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        return $this->set( $k, bcsub( $v, $step ));
    }

    // base64UrlEncode the data and sign it so it can't be tampered with by the user.
    protected function serialize( $data ){
        $payload = self::base64UrlEncode(serialize( $data ));
        $sig = hash_hmac('sha256', $payload, $this->__secret, $raw = true);
        $encoded_sig = self::base64UrlEncode($sig);
        return $encoded_sig . '.' . $payload;
    }
    
    // parse the value and deserialize it. make sure it hasn't been tampered with.
    protected function unserialize($signed_data) {
        if(! is_scalar( $signed_data ) ) return NULL;
        if( strpos( $signed_data, '.' ) === FALSE ) return NULL;
        list($encoded_sig, $payload) = explode('.', $signed_data, 2);
        $sig = self::base64UrlDecode($encoded_sig);
        $data = unserialize(self::base64UrlDecode($payload));
        $expected_sig = hash_hmac('sha256', $payload, $this->__secret, $raw = true);
        if ($sig !== $expected_sig) return NULL;
        return $data;
    }
    
    protected static function base64UrlEncode( $input ){
        return rtrim( strtr(base64_encode($input), '+/', '-_'), '=');
    }
    
    protected static function base64UrlDecode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}