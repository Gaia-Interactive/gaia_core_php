<?php
// make sure we can actually filter.
if( ! function_exists('filter_var') ) return $this;

// create a new container so we can put all the sanitized content inside
$data = $this->instance();

// loop throug all the current data
foreach( $this as $key=>$value) {
    // sanitize each variable and assign it to the new container.
    $this->$key = filter_var( $value, FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR | FILTER_FLAG_STRIP_LOW );
}

return $this;
// EOF