<?php
// extract the route
$route = $this->route;

// remove it from the parameter list
unset( $this->route );

$base_url = ( function_exists('filter_var') ) ? filter_var( $_SERVER['SCRIPT_NAME'] ) : $_SERVER['SCRIPT_NAME'];

// build the url and return it.
return $this->instance(
                        array( 'url'=> $base_url . '/' . preg_replace('/[^a-z0-9\/\_]/i', '', $route), 
                               'parameters'=>$this,
                        ) 
            )->dispatch( __DIR__ . '/url.php'); 

// EOF