<?php
namespace Gaia\Http;

class Util {

    public static function parseHeaders($headers) {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $headers));
        foreach( $fields as $field ) {
            if( ! preg_match('/([^:]+): (.+)/m', $field, $match) ) continue;
            $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
            if( isset($retVal[$match[1]]) ) {
                $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
            } else {
                $retVal[$match[1]] = trim($match[2]);
            }
          
        }
        return $retVal;
    }
    
    public static function buildQuery($params, $name=null) {
        if( is_object( $params ) ) $params = json_decode( json_encode( $params ), TRUE);
        if( ! is_array( $params ) ) return rawurlencode($params);
        $ret = "";
        foreach($params as $key=>$val) {
            $key = rawurlencode( $key );
            if(is_array($val)) {
                if($name==null) $ret .= self::buildQuery($val, $key);
                else $ret .= self::buildQuery($val, $name."[$key]");   
            } else {
                $val=rawurlencode($val);
                if($name!=null)
                $ret.=$name."[$key]"."=$val&";
                else $ret.= "$key=$val&";
            }
        }
        if( $name == null ) $ret = trim( $ret, '&');
        return $ret;   
    }
}