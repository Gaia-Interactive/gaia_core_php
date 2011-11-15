<?php
if( strpos( $this->REQUEST_URI, $this->SCRIPT_NAME ) === FALSE ) return 'index';

// extract the route name from the request URI
$route = trim( str_replace( $this->SCRIPT_NAME, '', $this->REQUEST_URI), ' /');

// trim off the GET params
if( $pos = strpos( $route, '?' ) ) $route = substr( $route, 0, $pos );

// get rid of any dangerous characters.
$route = preg_replace( '/[^a-z0-9\/\_]/', '', $route );

// if no route was specified, return the default characters
if( ! $route ) return 'index';

// all done.
return $route;

// EOF