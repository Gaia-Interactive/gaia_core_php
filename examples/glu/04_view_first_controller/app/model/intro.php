<?php
// shows how the action return ties to the view.

// build a data payload object.
$data = $this->instance();

// assign a title
$data->title = 'Welcome to GLU';

// assign the message
$data->message = 'This illustrates the basics';

// all done. return the data.
return $data;

// EOF