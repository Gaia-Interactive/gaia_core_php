<?php
// call the intro action so that we can build our page.
// pass along the input we got from the demo page so those vars can be
// used in the action.
$data = $this->instance($this->request)->dispatch(__DIR__ . '/../model/intro.php');

// render the page
$this->instance( 
    array(  'title'=>'Introduction',
            'message'=>array('header'=>$data->title, 'body'=>$data->message),
            'start'=>$this->start,
        ) )->dispatch(__DIR__ . '/layout/site.php'); 



// EOF
