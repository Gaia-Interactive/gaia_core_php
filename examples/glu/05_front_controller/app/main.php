<?php
// i am gonna turn on an output buffer so in case something bad happens mid-view render, i can
// discard it all and start over.
ob_start();

define('DIR_APP', __DIR__ . '/');

// we are gonna wrap in a try catch block.
// the reason is that in case our route doesn't exist  or some other include doesn't work, 
// we can easily catch the problem and go to an error view instead of just crashing in a
// Fatal Error in the script. 
try {

    // set up the request
    $this->request = $this->instance($this->request);
    
    // sanitize it.
    $this->request->dispatch( DIR_APP . 'util/sanitize.php');
    
    // call the controller. 
    $this->dispatch( DIR_APP . 'mvc/' . $this->route . '/controller.php');
    
// catch any exceptions
} catch( Exception $e ){

    // what happened? put the exception into the input
    // we can use it in the error template.
    $this->exception = $e;
    
    // let's grab everything so far in the output buffer and clear it.
    $this->debug = ob_get_clean();
    
    // start up the buffer again.
    ob_start();
    
    // nothing much left to do but render an error page
    return $this->dispatch( DIR_APP . 'layout/error.php');
}

// all done rendering: flush it out!
ob_end_flush();

// EOF