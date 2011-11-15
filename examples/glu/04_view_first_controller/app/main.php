<?php

// i am gonna turn on an output buffer so in case something bad happens mid-view render, i can
// discard it all and start over.
ob_start();


// package up the request as a glu.
$this->request = $this->instance($this->request);

// now i want to gather all the request variables, and while i am at it, sanitize it all
// using the built-in pecl filter_var function. i could just pass the request straight in, but
// it is a bit safer to sanitize it all first as a precaution, and it is easy to do.
// we could make up any number of sanitizers and filters. this was a quick and dirty one to 
// illustrate the point more than actually indicate how it should be used in production.
$this->request->dispatch(__DIR__ . '/lib/sanitize.php');

// we are gonna wrap in a try catch block.
// the reason is that in case our route doesn't exist  or some other include doesn't work, 
// we can easily catch the problem and go to an error view instead of just crashing in a
// Fatal Error in the script. 
try {
    // render the page
    $this->dispatch(__DIR__ . '/view/' . $this->view .'.php', $verify = TRUE);

// catch any exceptions
} catch( Exception $e ){

    // what happened? hold on to the exception
    // we can use it in the error template.
    $this->exception = $e;
    
    // let's grab everything so far in the output buffer and clear it.
    $this->debug = ob_get_clean();
    
    // start up the buffer again.
    ob_start();
    
    // nothing much left to do.
    // render the error view
    $this->dispatch(__DIR__ . '/view/error.php');
}

// all done rendering: flush it out!
ob_end_flush();

// all done!

// EOF
