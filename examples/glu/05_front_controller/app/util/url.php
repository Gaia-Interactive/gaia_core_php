<?php

$p = array();

$e = explode('?', $this->url, 2 );
$url = $e[0];
$query = isset( $e[1] ) ? $e[1] : '';
$parameters = array();
if( is_array( $this->parameters ) || $this->parameters instanceof Iterator ){
    foreach( $this->parameters as $k=>$v) $parameters[ $k ] = $v;
}
$amp = $this->amp;
if( ! $amp ) $amp = '&amp;';
if( strlen( $query ) > 0 )
{
    $string_params = explode( '&', $query);
    foreach( $string_params as $param ){
        if( strpos( $param, '=' ) === FALSE ) continue;
        list( $k, $v ) = explode('=', $param);
        if( ! isset($parameters[$k]) ) $parameters[$k] = $v;
    }
}
foreach ($parameters as $k => $v) {
    if( strlen( $v ) < 1 ) continue;
    $p[] = urlencode($k) . '=' . urlencode($v);
}

if( ! empty( $p ) ) $url .= '?' . implode($amp, $p );

return $url;