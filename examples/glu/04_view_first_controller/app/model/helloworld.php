<?php
// if we got a name, build a greeting.
// and set a flag saying we got a name
if( $this->name ) return $this->instance( array( 'greeting'=>'Howdy, ' . $this->name, 'name_posted'=>TRUE ) );

// or prompt to enter a name
return $this->instance( array('greeting'=>'Enter your name below'));


// EOF