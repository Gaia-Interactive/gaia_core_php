<?php
namespace Gaia\Serialize;

class QueryString implements Iface {

    public function unserialize( $v ){
        if( strpos($v, '=')===FALSE ) return urldecode($v);
        parse_str( $v, $res );
        if($res !== NULL) return $res;
        return urldecode( $v );
    }
    
    public function serialize($params, $name=null) {
        if( is_object( $params ) ) $params = json_decode( json_encode( $params ), TRUE);
        if( ! is_array( $params ) ) return urlencode($params);
        $ret = "";
        if( $name != NULL ) $name = urlencode( $name );
        foreach($params as $key=>$val) {
            $key = urlencode( $key );
            if(is_array($val)) {
                if($name==null) $ret .= $this->serialize($val, $key);
                else $ret .= $this->serialize($val, $name."[$key]");   
            } else {
                $val=urlencode($val);
                if($name!=null)
                $ret.=$name."[$key]"."=$val&";
                else $ret.= "$key=$val&";
            }
        }
        if( $name == null ) $ret = trim( $ret, '&');
        return $ret;   
    } 
}