<?php
// if we are running the ajax response, render the response
if( $this->request->response ) return $this->dispatch(__DIR__ . '/ajaxview.php');

// if it is the script, display the js
if( $this->request->script ) return $this->dispatch(DIR_APP . 'layout/callajaxscript.php');


// render the view
return $this->dispatch(__DIR__ . '/view.php');

// EOF